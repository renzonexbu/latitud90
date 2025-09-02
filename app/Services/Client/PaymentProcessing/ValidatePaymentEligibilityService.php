<?php

namespace App\Services\Client\PaymentProcessing;

use App\Traits\SystemLogging;

class ValidatePaymentEligibilityService
{
    use SystemLogging;

    /**
     * Validar el saldo pendiente del participante para el pago
     *
     * @param int $programId
     * @param string $rut
     * @param array $paymentData
     * @return array
     */
    public function execute(int $programId, string $rut, array $paymentData): array
    {
        // Buscar el participante
        $participant = \App\Models\Participant::where('document_number', $rut)->first();
        if (!$participant) {
            return [
                'success' => false,
                'error' => 'Participante no encontrado'
            ];
        }

        // Buscar el programa
        $program = \App\Models\Program::find($programId);
        if (!$program) {
            return [
                'success' => false,
                'error' => 'Programa no encontrado'
            ];
        }

        // Calcular el saldo pendiente del participante
        $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        $participantTotalAmount = $priceData['final_price'];

        // Pagos aprobados y completados previos
        $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
            $q->where('participant_id', $participant->id)
                ->where('program_id', $program->id);
        })
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');
        $paidAmount = round($paidAmount, 2);
        $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);

        // Verificar si ya se pagó todo
        if ($participantBalance <= 0) {
            return [
                'success' => false,
                'error' => 'Ya has pagado el monto total del programa. No hay pagos pendientes.'
            ];
        }

        // Para pagos mensuales, verificar que el total de las cuotas no exceda el saldo
        if (($paymentData['paymentType'] ?? 'total') === 'monthly') {
            $installments = (int) ($paymentData['installments'] ?? 1);
            $amountPerInstallment = $participantBalance / $installments;
            $totalToPay = $amountPerInstallment * $installments;

            if ($totalToPay > $participantBalance) {
                return [
                    'success' => false,
                    'error' => 'El monto total de las cuotas excede el saldo pendiente. Por favor, selecciona un número menor de cuotas.'
                ];
            }
        }

        $this->logInfo('Payment eligibility validated', [
            'participant_id' => $participant->id,
            'program_id' => $programId,
            'participant_total_amount' => $participantTotalAmount,
            'paid_amount' => $paidAmount,
            'participant_balance' => $participantBalance,
            'payment_type' => $paymentData['paymentType'] ?? 'total',
            'installments' => $paymentData['installments'] ?? 1
        ]);

        return [
            'success' => true,
            'participant' => $participant,
            'program' => $program,
            'participant_balance' => $participantBalance
        ];
    }
}
