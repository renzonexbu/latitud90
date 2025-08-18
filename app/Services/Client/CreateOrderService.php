<?php

namespace App\Services\Client;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Country;
use App\Models\Region;
use App\Models\Comune;
use App\Models\Document;
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
                    // Rebalancear por si hay cuotas vencidas (sin alterar montos a 0)
                    $this->rebalanceOverdueAmounts($existing);
                    // Elegir SIEMPRE la cuota no pagada más antigua (incluye vencidas)
                    $nextPayable = $existing->orderDetails()
                        ->where('is_paid', false)
                        ->where('amount', '>', 0)
                        ->orderBy('due_date')
                        ->first();
                    
                    // SIEMPRE actualizar los datos del comprador y método de pago en la cuota existente
                    $this->updateExistingOrderDetail($nextPayable, $paymentData, $formData);
                    
                    return [
                        'success' => true,
                        'order' => $existing,
                        'order_detail' => $nextPayable,
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
        // 1) Preferir el precio de participant_program (precio individual del participante para este programa)
        $pp = DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->where('program_id', $program->id)
            ->first();

        if ($pp) {
            $participantAmount = (float) ($pp->individual_price ?? 0);
            $participantAdjustments = 0.0; // Los ajustes ya deberían estar reflejados en individual_price
        } else {
            // 2) Fallback al pivote antiguo participant_course si existe
            $program->loadMissing('course.participants');
            $pivotParticipant = $program->course?->participants?->firstWhere('id', $participant->id);
            $participantAmount = (float) ($pivotParticipant->pivot->individual_price ?? $participant->individual_price ?? $program->trip_price);
            $participantAdjustments = (float) ($pivotParticipant->pivot->price_adjustments ?? 0);
        }

        $participantTotalAmount = round(($participantAmount + $participantAdjustments), 2);

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

        // Marcar vencidas como overdue pero NO poner amount=0. Se pagarán individualmente o se re-balancearán explícitamente.
        foreach ($overdueUnpaid as $detail) {
            $detail->status = 'overdue';
            $detail->save();
        }

        // Ya no redistribuimos automáticamente para no inflar la siguiente cuota visualmente.
    }
    private function createOrderDetail($order, $paymentData, $formData, $installmentNumber, $amount, $dueDate = null)
    {
        // Determinar payment_option_id según el nuevo esquema
        $paymentOptionId = $this->resolvePaymentOptionId((int) $order->program_id, $paymentData);

        // Mapear las claves del frontend (camelCase) a las claves del backend (snake_case)
        // Preferir buyerData si viene anidado (por compatibilidad futura)
        if (isset($formData['buyerData']) && is_array($formData['buyerData'])) {
            $formData = array_merge($formData, $formData['buyerData']);
        }

        $mappedFormData = [
            'name' => $formData['name'] ?? ($formData['fullName'] ?? null),
            'email' => $formData['email'] ?? null,
            // IDs para persistencia
            'country' => $formData['countryId'] ?? ($formData['country'] ?? null),
            'region' => $formData['regionId'] ?? ($formData['region'] ?? null),
            'city' => $formData['cityId'] ?? ($formData['city'] ?? null),
            'code_phone' => $formData['code_phone'] ?? null,
            'phone' => $formData['phone'] ?? null,
            'document_type' => $formData['documentType'] ?? null,
            'document_number' => $formData['documentNumber'] ?? null,
            'terms_accepted' => $formData['termsAccepted'] ?? false,
            'marketing_accepted' => $formData['marketingAccepted'] ?? false,
            // Nombres para facturación
            'country_name' => $formData['countryName'] ?? null,
            'region_name' => $formData['regionName'] ?? null,
            'city_name' => $formData['cityName'] ?? null,
        ];

        // Normalizar IDs (acepta id, código, o nombre)
        $countryId = $this->resolveCountryId($mappedFormData['country'] ?? null);
        $regionId = $this->resolveRegionId($mappedFormData['region'] ?? null);
        $cityId = $this->resolveCityId($mappedFormData['city'] ?? null);
        $documentTypeId = $this->resolveDocumentTypeId($mappedFormData['document_type'] ?? null);

        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOptionId,
            
            // Datos del comprador
            'name' => $mappedFormData['name'],
            'email' => $mappedFormData['email'],
            'country' => $countryId,
            'region' => $regionId,
            'city' => $cityId,
            'code_phone' => $mappedFormData['code_phone'],
            'phone' => $mappedFormData['phone'],
            'document_type' => $documentTypeId,
            'document_number' => $mappedFormData['document_number'],
            
            // Dirección de facturación (por ahora usando los mismos datos)
            'billing_address' => $formData['billing_address'] ?? null,
            'billing_city' => $mappedFormData['city_name'] ?? $mappedFormData['city'],
            'billing_country' => $mappedFormData['country_name'] ?? $mappedFormData['country'],
            'billing_postal_code' => $formData['billing_postal_code'] ?? null,
            
            // Acuerdos
            'terms_accepted' => $mappedFormData['terms_accepted'],
            'marketing_accepted' => $mappedFormData['marketing_accepted'],
            'terms_accepted_confirmation' => $paymentData['termsAccepted'] ?? false,
            
            // Información de la cuota
            'installment_number' => $installmentNumber,
            'base_amount' => $amount,
            'discount_amount' => 0,
            'amount' => $amount,
            'due_date' => $dueDate ?? now(),
            'is_paid' => false,
            'status' => 'pending',
        ]);
    }

    private function updateExistingOrderDetail(OrderDetail $orderDetail, array $paymentData, array $formData)
    {
        // Determinar payment_option_id según el nuevo esquema
        $paymentOptionId = $this->resolvePaymentOptionId((int) $orderDetail->order->program_id, $paymentData);

        // Mapear las claves del frontend (camelCase) a las claves del backend (snake_case)
        // Preferir buyerData si viene anidado (por compatibilidad futura)
        if (isset($formData['buyerData']) && is_array($formData['buyerData'])) {
            $formData = array_merge($formData, $formData['buyerData']);
        }

        $mappedFormData = [
            'name' => $formData['name'] ?? ($formData['fullName'] ?? null),
            'email' => $formData['email'] ?? null,
            // IDs para persistencia
            'country' => $formData['countryId'] ?? ($formData['country'] ?? null),
            'region' => $formData['regionId'] ?? ($formData['region'] ?? null),
            'city' => $formData['cityId'] ?? ($formData['city'] ?? null),
            'code_phone' => $formData['code_phone'] ?? null,
            'phone' => $formData['phone'] ?? null,
            'document_type' => $formData['documentType'] ?? null,
            'document_number' => $formData['documentNumber'] ?? null,
            'terms_accepted' => $formData['termsAccepted'] ?? false,
            'marketing_accepted' => $formData['marketingAccepted'] ?? false,
            // Nombres para facturación
            'country_name' => $formData['countryName'] ?? null,
            'region_name' => $formData['regionName'] ?? null,
            'city_name' => $formData['cityName'] ?? null,
        ];

        // Normalizar IDs (acepta id, código, o nombre)
        $countryId = $this->resolveCountryId($mappedFormData['country'] ?? null);
        $regionId = $this->resolveRegionId($mappedFormData['region'] ?? null);
        $cityId = $this->resolveCityId($mappedFormData['city'] ?? null);
        $documentTypeId = $this->resolveDocumentTypeId($mappedFormData['document_type'] ?? null);

        $orderDetail->update([
            'payment_option_id' => $paymentOptionId,
            
            // Datos del comprador
            'name' => $mappedFormData['name'],
            'email' => $mappedFormData['email'],
            'country' => $countryId,
            'region' => $regionId,
            'city' => $cityId,
            'code_phone' => $mappedFormData['code_phone'],
            'phone' => $mappedFormData['phone'],
            'document_type' => $documentTypeId,
            'document_number' => $mappedFormData['document_number'],
            
            // Dirección de facturación (por ahora usando los mismos datos)
            'billing_address' => $formData['billing_address'] ?? null,
            'billing_city' => $mappedFormData['city_name'] ?? $mappedFormData['city'],
            'billing_country' => $mappedFormData['country_name'] ?? $mappedFormData['country'],
            'billing_postal_code' => $formData['billing_postal_code'] ?? null,
            
            // Acuerdos
            'terms_accepted' => $mappedFormData['terms_accepted'],
            'marketing_accepted' => $mappedFormData['marketing_accepted'],
            'terms_accepted_confirmation' => $paymentData['termsAccepted'] ?? false,
        ]);
        
        Log::info('Existing OrderDetail updated with new buyer data and payment method', [
            'order_detail_id' => $orderDetail->id,
            'installment_number' => $orderDetail->installment_number,
            'payment_option_id' => $paymentOptionId,
            'buyer_name' => $mappedFormData['name'],
            'buyer_email' => $mappedFormData['email'],
            'payment_method' => $paymentData['paymentMethod'] ?? 'unknown'
        ]);
    }

    private function resolvePaymentOptionId(int $programId, array $paymentData): ?int
    {
        // Elegir modo y construir code
        $mode = ($paymentData['paymentType'] ?? 'total') === 'monthly' ? 'lat90' : 'full';
        $method = $paymentData['paymentMethod'] ?? 'debit';
        $code = null;
        if ($mode === 'full') {
            switch ($method) {
                case 'khipu': $code = 'full_transfer_khipu'; break;
                case 'debit': $code = 'full_debit_webpay'; break;
                case 'credit_0': $code = 'full_credit_webpay_0'; break;
                case 'credit_3': $code = 'full_credit_webpay_3'; break;
                case 'credit_6': $code = 'full_credit_webpay_6'; break;
                case 'credit_9': $code = 'full_credit_webpay_9'; break;
                case 'credit_12': $code = 'full_credit_webpay_12'; break;
                default:
                    // si enviaron 'credit' sin cuotas, tratar como 0
                    if (strpos($method, 'credit') === 0) {
                        $suffix = trim(str_replace('credit', '', $method), '_');
                        $n = $suffix !== '' ? (int)$suffix : 0;
                        $code = 'full_credit_webpay_' . $n;
                    }
                    break;
            }
        } else {
            switch ($method) {
                case 'khipu': $code = 'lat90_transfer_khipu'; break;
                case 'debit': $code = 'lat90_debit_webpay'; break;
                case 'credit': $code = 'lat90_credit_0'; break; // crédito mensual se procesa 1 cuota por transacción
            }
        }

        if (!$code) { return null; }

        // Validar que el programa tenga esta opción habilitada (pivot)
        $optionId = DB::table('payment_options')->where('code', $code)->value('id');
        if (!$optionId) { return null; }
        $enabled = DB::table('program_payment_option')
            ->where('program_id', $programId)
            ->where('payment_option_id', $optionId)
            ->where('enabled', true)
            ->exists();
        return $enabled ? (int)$optionId : null;
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

    private function resolveCountryId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        // Probar por código (CL, etc.)
        if (strlen($string) <= 3) {
            $id = Country::where('code', $string)->value('id');
            if ($id) { return (int) $id; }
        }
        // Fallback por nombre exacto
        $id = Country::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        // Fallback por like
        $id = Country::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveRegionId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = Region::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = Region::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveCityId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = Comune::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = Comune::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    private function resolveDocumentTypeId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        $string = trim((string) $value);
        $id = Document::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = Document::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }
}
