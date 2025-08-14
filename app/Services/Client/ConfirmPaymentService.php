<?php

namespace App\Services\Client;

use App\Models\Program;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmPaymentService
{
    public function getConfirmationDetails($programId, $participantId = null, $rut = null)
    {
        $program = Program::with(['course.participants'])->find($programId);
        if (!$program) {
            return null;
        }

        // Buscar datos reales del participante por RUT
        $formData = $this->getFormDataFromRut($rut);
        $participant = null;
        if ($rut) {
            $participant = Participant::where('document_number', preg_replace('/[.-]/', '', $rut))
                ->where('document_type', 'RUT')
                ->first();
        } elseif ($participantId) {
            $participant = Participant::find($participantId);
        }

        // Calcular montos por participante
        $isEnrolled = false;
        $participantAmount = null;
        $participantAdjustments = 0.0;
        $participantTotalAmount = (float) $program->trip_price;
        $paidAmount = 0.0;
        $participantBalance = (float) $program->trip_price;
        $paymentPercentage = 0.0;

        if ($participant && $program->course) {
            $pivotParticipant = $program->course->participants
                ->firstWhere('id', $participant->id);
            if ($pivotParticipant) {
                $isEnrolled = true;
                $participantAmount = (float) ($pivotParticipant->pivot->individual_price ?? $participant->individual_price ?? $program->trip_price);
                $participantAdjustments = (float) ($pivotParticipant->pivot->price_adjustments ?? 0);
                $participantTotalAmount = round($participantAmount + $participantAdjustments, 2);
                // Sumar por cuotas efectivamente pagadas en OrderDetail (más fiable)
                $paidAmount = (float) \App\Models\OrderDetail::whereHas('order', function ($q) use ($participant, $program) {
                        $q->where('participant_id', $participant->id)
                          ->where('program_id', $program->id);
                    })
                    ->where('is_paid', true)
                    ->sum('amount');
                $paidAmount = round($paidAmount, 2);
                $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);
                $paymentPercentage = $participantTotalAmount > 0
                    ? round(($paidAmount / $participantTotalAmount) * 100, 2)
                    : 0.0;
            }
        }
        $paymentData = $this->getPaymentDataFromSession();
        // Orden mensual activa: próxima cuota y bloqueo de plan
        $activeInstallment = null;
        $paymentPlanLocked = false;
        if ($participant) {
            $order = Order::where('participant_id', $participant->id)
                ->where('program_id', $program->id)
                ->where('payment_type', 'monthly')
                ->latest('id')
                ->first();
            if ($order) {
                // Bloquear selección si alguna cuota ya fue pagada
                $paymentPlanLocked = $order->orderDetails()->where('is_paid', true)->exists();
                $today = now()->startOfDay();
                $overdueUnpaid = $order->orderDetails()
                    ->where('is_paid', false)
                    ->whereDate('due_date', '<', $today)
                    ->get();
                $sumOverdue = round($overdueUnpaid->sum('amount'), 2);
                $next = $order->orderDetails()
                    ->where('is_paid', false)
                    ->orderBy('due_date')
                    ->first();
                if ($next) {
                    // Mostrar SOLO el monto de la próxima cuota.
                    // Las cuotas vencidas se pagan individualmente o se re-balancean en backend si corresponde.
                    $activeInstallment = [
                        'number' => (int) $next->installment_number,
                        'total' => (int) $order->total_installments,
                        'amount' => round(((float) $next->amount), 2),
                        'due_date' => optional($next->due_date)->toDateString(),
                    ];
                }
            }
        }

        // Cargar opciones de pago habilitadas (nuevo esquema payment_options + pivote)
        $fullPaymentOptionCodes = DB::table('program_payment_option as ppo')
            ->join('payment_options as po', 'po.id', '=', 'ppo.payment_option_id')
            ->where('ppo.program_id', $program->id)
            ->where('ppo.enabled', true)
            ->where('po.mode', 'full')
            ->pluck('po.code')
            ->toArray();

        $lat90PaymentOptions = DB::table('program_payment_option as ppo')
            ->join('payment_options as po', 'po.id', '=', 'ppo.payment_option_id')
            ->select(['po.code', 'po.label'])
            ->where('ppo.program_id', $program->id)
            ->where('ppo.enabled', true)
            ->where('po.mode', 'lat90')
            ->get()
            ->map(function ($row) {
                return ['code' => $row->code, 'label' => $row->label];
            })
            ->toArray();

        return [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'destination' => $program->destination,
                // Mostrar siempre valores para el participante
                // Base para UI (valor del participante)
                'trip_price' => $participantTotalAmount,
                'departure_date' => $program->departure_date,
                'final_payment_date' => $program->final_payment_date,
                'max_installments' => $program->max_installments,
                // Propiedades necesarias para PaymentPanel
                'enable_total_payment' => $program->enable_total_payment,
                'enable_lat90_payment' => $program->enable_lat90_payment,
                'full_payment_options' => $fullPaymentOptionCodes,
                'lat90_payment_options' => $lat90PaymentOptions,
                'lat90_max_installments' => $program->lat90_max_installments,
                // Montos por participante
                'participant_amount' => $participantAmount,
                'participant_adjustments' => $participantAdjustments,
                'participant_total_due' => $participantTotalAmount,
                'paidAmount' => $paidAmount,
                'participant_balance' => $participantBalance,
                'paymentPercentage' => $paymentPercentage,
                'payment_plan_locked' => $paymentPlanLocked,
                'active_installment' => $activeInstallment,
            ],
            'participant' => $participantId ? [
                'id' => $participantId,
            ] : null,
            'form_data' => $formData,
            'payment_data' => $paymentData,
            'confirmation_number' => 'CONF-' . time() . '-' . rand(1000, 9999),
            'status' => 'confirmed'
        ];
    }

    private function getFormDataFromRut($rut)
    {
        if (!$rut) {
            return $this->getFormDataFromSession();
        }

        // Buscar participante por RUT en la tabla participants
        $participant = Participant::where('document_number', $rut)->first();
        
        if ($participant) {
            $formData = [
                'name' => $participant->first_name . ' ' . $participant->last_name,
                'document_number' => $participant->document_number,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'code_phone' => $participant->code_phone ?? '+56',
                'country' => $participant->country ?? 'Chile',
                'region' => '',
                'city' => '',
                'termsAccepted' => true,
                'marketingAccepted' => true
            ];
            
            return $formData;
        }

        // Si no se encuentra, devolver datos por defecto
        return $this->getFormDataFromSession();
    }

    private function getFormDataFromSession()
    {
        // En un caso real, esto vendría de la base de datos
        return [
            'name' => 'Usuario Ejemplo',
            'document_number' => '12345678-9',
            'email' => 'usuario@ejemplo.com',
            'phone' => '912345678',
            'code_phone' => '+56',
            'country' => 'Chile',
            'region' => 'metropolitana',
            'city' => 'santiago',
            'termsAccepted' => true,
            'marketingAccepted' => true
        ];
    }

    private function getPaymentDataFromSession()
    {
        // En un caso real, esto vendría de la base de datos
        return [
            'paymentType' => 'total',
            'paymentMethod' => 'debit',
            'installments' => 1
        ];
    }
}
