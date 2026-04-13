<?php

namespace App\Services\Client\PaymentProcessing;

use App\Traits\SystemLogging;

class ValidatePaymentEligibilityService
{
    use SystemLogging;

    /**
     * Validar el saldo pendiente del participante para el pago
     *
     * @param int $programId - En realidad es el ID de ProgramCourse
     * @param string $rut
     * @param array $paymentData
     * @return array
     */
    public function execute(int $programId, string $rut, array $paymentData): array
    {
        // Buscar el participante (validación de programa activo se hace después)
        $cleanRut = preg_replace('/[.-]/', '', $rut);
        $participant = \App\Models\Participant::where('document_number', $cleanRut)->first();
        if (!$participant) {
            return [
                'success' => false,
                'error' => 'Participante no encontrado'
            ];
        }

        // Buscar el ProgramCourse (no Program)
        // IMPORTANTE: $programId es en realidad un ProgramCourse ID
        // Cargar la relación 'course' con sus participantes para que ParticipantPriceHelper funcione
        $programCourse = \App\Models\ProgramCourse::with(['course.participants'])->find($programId);
        if (!$programCourse) {
            return [
                'success' => false,
                'error' => 'Programa no encontrado'
            ];
        }

        // Calcular el saldo pendiente del participante
        $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
        $participantTotalAmount = $priceData['final_price'];

        // Pagos aprobados y completados previos
        $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
            $q->where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id); // program_id en orders apunta a program_courses
        })
            ->whereIn('status', ['approved', 'completed'])
            ->sum('amount');
        $paidAmount = round($paidAmount, 2);
        $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);

        \Log::info('=== ValidatePaymentEligibility DEBUG ===', [
            'participant_id' => $participant->id,
            'program_course_id' => $programId,
            'participantTotalAmount' => $participantTotalAmount,
            'paidAmount' => $paidAmount,
            'participantBalance' => $participantBalance,
        ]);

        // Verificar si ya se pagó todo
        // IMPORTANTE: Solo validar si paidAmount > 0 (hay pagos previos)
        if ($participantBalance <= 0 && $paidAmount > 0) {
            return [
                'success' => false,
                'error' => 'Ya has pagado el monto total del programa. No hay pagos pendientes.'
            ];
        }

        // Para pagos mensuales, validar número mínimo de cuotas
        if (($paymentData['paymentType'] ?? 'total') === 'monthly') {
            $installments = (int) ($paymentData['installments'] ?? 1);

            // Validar que al menos se pueda dividir en 1 peso por cuota
            if ($installments > $participantBalance) {
                return [
                    'success' => false,
                    'error' => 'El número de cuotas es muy alto para el saldo pendiente. Por favor, selecciona un número menor de cuotas.'
                ];
            }
        }

        $this->logInfo('Payment eligibility validated', [
            'participant_id' => $participant->id,
            'program_course_id' => $programId,
            'participant_total_amount' => $participantTotalAmount,
            'paid_amount' => $paidAmount,
            'participant_balance' => $participantBalance,
            'payment_type' => $paymentData['paymentType'] ?? 'total',
            'installments' => $paymentData['installments'] ?? 1
        ]);

        return [
            'success' => true,
            'participant' => $participant,
            'program' => $programCourse, // Retorna ProgramCourse, no Program
            'participant_balance' => $participantBalance
        ];
    }
}
