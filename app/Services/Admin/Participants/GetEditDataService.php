<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\Program;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Facades\Log;

class GetEditDataService
{
    /**
     * Obtener datos necesarios para editar un participante
     *
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function execute(int $id): array
    {
        try {
            // Buscar el participante
            $participant = Participant::findOrFail($id);

            // Cargar relaciones
            $participant->load(['courses', 'courses.institution', 'courses.program', 'emergencyContacts']);

            // Cargar participant_programs con sus descuentos
            $participantPrograms = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                ->with(['program', 'discounts'])
                ->get();

            // Obtener datos de programas del participante con precios calculados
            $participantProgramsData = $this->getParticipantProgramsData($participant, $participantPrograms);

            return [
                'participant' => $participant,
                'participantPrograms' => $participantProgramsData,
                'participantProgramsWithDiscounts' => $participantPrograms,
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener datos para editar participante', [
                'participant_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Obtener datos de programas del participante con precios calculados
     *
     * @param Participant $participant
     * @param \Illuminate\Support\Collection $participantPrograms
     * @return \Illuminate\Support\Collection
     */
    private function getParticipantProgramsData(Participant $participant, $participantPrograms)
    {
        return Program::whereHas('course.participants', function ($query) use ($participant) {
            $query->where('participants.id', $participant->id);
        })
            ->with(['course' => function ($q) use ($participant) {
                $q->with(['institution', 'participants' => function ($qp) use ($participant) {
                    $qp->where('participants.id', $participant->id);
                }]);
            }])
            ->get()
            ->map(function ($program) use ($participant, $participantPrograms) {
                // Usar el helper para calcular el precio final con descuentos
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);

                // Pagos aprobados/completados del participante para este programa
                $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                    $q->where('participant_id', $participant->id)
                        ->where('program_id', $program->id);
                })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');
                $paidAmount = round($paidAmount, 2);
                $balance = max(round($priceData['final_price'] - $paidAmount, 2), 0);
                $paymentPercentage = ($priceData['final_price'] > 0)
                    ? round(($paidAmount / $priceData['final_price']) * 100, 0)
                    : 0;

                $array = $program->toArray();
                $array['participant_amount'] = $priceData['base_price']; // precio base por participante
                $array['participant_adjustments'] = $priceData['adjustments']; // ajuste del pivote
                $array['participant_total_due'] = $priceData['final_price']; // total a pagar (base + ajuste - descuentos)
                $array['paidAmount'] = $paidAmount;
                $array['participant_balance'] = $balance;
                $array['paymentPercentage'] = $paymentPercentage;
                return $array;
            });
    }
}
