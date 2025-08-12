<?php

namespace App\Services\Client;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Participant;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateOrderService
{
    public function createOrder($programId, $rut, $paymentData, $formData)
    {
        try {
            DB::beginTransaction();

            // Buscar el participante por RUT
            $participant = Participant::where('document_number', $rut)->first();
            if (!$participant) {
                throw new \Exception('Participante no encontrado');
            }

            // Buscar el programa
            $program = Program::with(['course.participants'])->findOrFail($programId);

            // Calcular montos por participante desde pivote y pagos previos
            [$participantTotalAmount, $paidAmount, $participantBalance] = $this->computeParticipantAmounts($program, $participant);
            $finalAmount = $participantBalance; // Base para este plan de pago

            // Determinar número total de cuotas
            $totalInstallments = $paymentData['paymentType'] === 'monthly'
                ? (int) ($paymentData['installments'] ?? ($program->lat90_max_installments ?? 1))
                : 1;
            if ($totalInstallments < 1) { $totalInstallments = 1; }

            // Si ya existe una orden mensual pendiente con cuotas impagas, devolver esa orden (no crear otra)
            if ($paymentData['paymentType'] === 'monthly') {
                $existing = Order::where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->where('payment_type', 'monthly')
                    ->whereHas('orderDetails', function ($q) {
                        $q->where('is_paid', false);
                    })
                    ->latest('id')
                    ->first();
                if ($existing) {
                    // Rebalancear por si hay cuotas vencidas
                    $this->rebalanceOverdueAmounts($existing);
                    return [
                        'success' => true,
                        'order' => $existing,
                        'order_detail' => $existing->orderDetails()
                            ->where('is_paid', false)
                            ->orderBy('due_date')
                            ->first(),
                    ];
                }
            }

            // Crear la orden principal
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'total_amount' => $participantTotalAmount,
                'discount' => 0,
                'final_amount' => $finalAmount,
                'total_installments' => $totalInstallments,
                'payment_type' => $paymentData['paymentType'],
                'status' => 'pending',
                'order_number' => $this->generateOrderNumber(),
                'notes' => 'Orden creada desde el flujo de pago'
            ]);

            // Crear cuotas según tipo de pago
            if ($paymentData['paymentType'] === 'monthly' && $totalInstallments > 1) {
                $amounts = $this->splitAmountInInstallments($finalAmount, $totalInstallments);
                $dueDates = $this->generateMonthlyDueDates($program, $totalInstallments);
                for ($i = 1; $i <= $totalInstallments; $i++) {
                    $this->createOrderDetail($order, $paymentData, $formData, $i, $amounts[$i - 1], $dueDates[$i - 1]);
                }
            } else {
                // Pago total: una sola cuota por el monto final
                $singleDueDate = $program->final_payment_date
                    ? Carbon::parse($program->final_payment_date)
                    : now();
                $this->createOrderDetail($order, $paymentData, $formData, 1, $finalAmount, $singleDueDate);
            }

            DB::commit();

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'participant_rut' => $rut,
                'program_id' => $programId,
                'total_amount' => $finalAmount,
                'installments' => $totalInstallments
            ]);

            return [
                'success' => true,
                'order' => $order,
                'order_detail' => $order->orderDetails->first()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order', [
                'error' => $e->getMessage(),
                'program_id' => $programId,
                'rut' => $rut
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Calcula descuento del programa y retorna [descuento_aplicado, monto_final]
     */
    private function applyProgramDiscount(Program $program, float $baseAmount): array
    {
        $discountType = $program->discount_type; // porcentaje_10 | porcentaje_15 | porcentaje_20 | monto_fijo | null
        $discountValue = $program->discount_value; // porcentaje (0.10) o monto fijo

        if (!$discountType || !$discountValue) {
            return [0.0, round($baseAmount, 2)];
        }

        if ($discountType === 'monto_fijo') {
            $discount = min($baseAmount, (float) $discountValue);
            return [round($discount, 2), round($baseAmount - $discount, 2)];
        }

        // Asumimos valor porcentual en decimal (e.g., 0.10)
        $percent = (float) $discountValue;
        if ($percent <= 0) {
            return [0.0, round($baseAmount, 2)];
        }
        $discount = round($baseAmount * $percent, 2);
        return [$discount, round($baseAmount - $discount, 2)];
    }

    /**
     * Obtiene total por participante (base+ajuste), total pagado y saldo pendiente.
     */
    private function computeParticipantAmounts(Program $program, Participant $participant): array
    {
        $program->loadMissing('course.participants');
        $pivotParticipant = $program->course?->participants?->firstWhere('id', $participant->id);
        $participantAmount = (float) ($pivotParticipant->pivot->individual_price ?? $participant->individual_price ?? $program->trip_price);
        $participantAdjustments = (float) ($pivotParticipant->pivot->price_adjustments ?? 0);
        $participantTotalAmount = round($participantAmount + $participantAdjustments, 2);

        // Pagos aprobados previos de este participante para este programa
        $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $program->id);
            })
            ->where('status', 'approved')
            ->sum('amount');
        $paidAmount = round($paidAmount, 2);
        $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);

        return [$participantTotalAmount, $paidAmount, $participantBalance];
    }

    /**
     * Divide un monto en N cuotas cuidando redondeo para que la suma sea exacta.
     */
    private function splitAmountInInstallments(float $total, int $installments): array
    {
        $base = floor(($total / $installments) * 100) / 100; // 2 decimales hacia abajo
        $amounts = array_fill(0, $installments, $base);
        $allocated = $base * $installments;
        $remainder = round($total - $allocated, 2);

        // Distribuir centavos restantes sumando 0.01 a las primeras cuotas
        $i = 0;
        while ($remainder > 0 && $i < $installments) {
            $amounts[$i] = round($amounts[$i] + 0.01, 2);
            $remainder = round($remainder - 0.01, 2);
            $i++;
        }
        return $amounts;
    }

    /**
     * Genera fechas de vencimiento mensuales.
     * - Si el programa tiene final_payment_date, la última cuota vence ese día y las anteriores se van restando meses.
     * - Si no, usa el día actual como día base y genera hacia adelante.
     */
    private function generateMonthlyDueDates(Program $program, int $installments): array
    {
        $dates = [];
        if ($program->final_payment_date) {
            $last = Carbon::parse($program->final_payment_date);
            for ($i = $installments - 1; $i >= 0; $i--) {
                $dates[$i] = $last->copy()->subMonthsNoOverflow(($installments - 1) - $i);
            }
            ksort($dates);
            return array_values($dates);
        }

        $base = now();
        $baseDay = $base->day;
        for ($i = 0; $i < $installments; $i++) {
            $month = $base->copy()->addMonthsNoOverflow($i);
            $dates[] = $month->copy()->day(min($baseDay, $month->daysInMonth));
        }
        return $dates;
    }

    /**
     * Rebalancea montos cuando existen cuotas vencidas impagas.
     * Suma lo vencido impago y lo redistribuye equitativamente entre las cuotas futuras impagas.
     */
    public function rebalanceOverdueAmounts(Order $order): void
    {
        $today = Carbon::today();
        $overdueUnpaid = $order->orderDetails()
            ->where('due_date', '<', $today)
            ->where('is_paid', false)
            ->get();

        if ($overdueUnpaid->isEmpty()) {
            return;
        }

        $remaining = $order->orderDetails()
            ->whereDate('due_date', '>=', $today)
            ->where('is_paid', false)
            ->orderBy('due_date')
            ->get();

        if ($remaining->isEmpty()) {
            return;
        }

        $sumOverdue = round($overdueUnpaid->sum('amount'), 2);

        // Marcar vencidas como overdue y dejar en 0 para no duplicar deuda
        foreach ($overdueUnpaid as $detail) {
            $detail->status = 'overdue';
            $detail->amount = 0.00;
            $detail->save();
        }

        // Redistribuir entre las restantes
        $additions = $this->splitAmountInInstallments($sumOverdue, $remaining->count());
        foreach ($remaining as $index => $detail) {
            $detail->amount = round($detail->amount + $additions[$index], 2);
            $detail->save();
        }
    }
    private function createOrderDetail($order, $paymentData, $formData, $installmentNumber, $amount, $dueDate = null)
    {
        // Determinar el método de pago y modo de pago según la selección
        $paymentMethodMapping = [
            'debit' => 1, // Asumiendo que 1 es tarjeta de débito
            'credit' => 2, // Asumiendo que 2 es tarjeta de crédito
            'khipu' => 3, // Asumiendo que 3 es transferencia Khipu
        ];

        $paymentModeMapping = [
            'total' => 1, // Pago total
            'monthly' => 2, // Pago mensual
        ];

        // Determinar el gateway de pago según el método
        $gatewayMapping = [
            'debit' => 1, // Transbank
            'credit' => 1, // Transbank
            'khipu' => 2, // Khipu
        ];

        // Mapear las claves del frontend (camelCase) a las claves del backend (snake_case)
        $mappedFormData = [
            'name' => $formData['name'] ?? null,
            'email' => $formData['email'] ?? null,
            'country' => $formData['countryName'] ?? null,
            'region' => $formData['regionName'] ?? null,
            'city' => $formData['cityName'] ?? null,
            'code_phone' => $formData['code_phone'] ?? null,
            'phone' => $formData['phone'] ?? null,
            'document_type' => $formData['documentType'] ?? null,
            'document_number' => $formData['documentNumber'] ?? null,
            'terms_accepted' => $formData['termsAccepted'] ?? false,
            'marketing_accepted' => $formData['marketingAccepted'] ?? false,
        ];

        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethodMapping[$paymentData['paymentMethod']] ?? 1,
            'payment_mode_id' => $paymentModeMapping[$paymentData['paymentType']] ?? 1,
            'payment_gateway_id' => $gatewayMapping[$paymentData['paymentMethod']] ?? 1,
            
            // Datos del comprador
            'name' => $mappedFormData['name'],
            'email' => $mappedFormData['email'],
            'country' => $mappedFormData['country'],
            'region' => $mappedFormData['region'],
            'city' => $mappedFormData['city'],
            'code_phone' => $mappedFormData['code_phone'],
            'phone' => $mappedFormData['phone'],
            'document_type' => $mappedFormData['document_type'],
            'document_number' => $mappedFormData['document_number'],
            
            // Dirección de facturación (por ahora usando los mismos datos)
            'billing_address' => $formData['billing_address'] ?? null,
            'billing_city' => $mappedFormData['city'],
            'billing_country' => $mappedFormData['country'],
            'billing_postal_code' => $formData['billing_postal_code'] ?? null,
            
            // Acuerdos
            'terms_accepted' => $mappedFormData['terms_accepted'],
            'marketing_accepted' => $mappedFormData['marketing_accepted'],
            'terms_accepted_confirmation' => $paymentData['termsAccepted'] ?? false,
            
            // Información de la cuota
            'installment_number' => $installmentNumber,
            'amount' => $amount,
            'due_date' => $dueDate ?? now(),
            'is_paid' => false,
            'status' => 'pending',
        ]);
    }

    private function generateOrderNumber()
    {
        $prefix = 'ORD';
        $year = date('Y');
        $month = date('m');
        $sequence = Order::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->count() + 1;
        
        return sprintf('%s-%s%s-%06d', $prefix, $year, $month, $sequence);
    }
}
