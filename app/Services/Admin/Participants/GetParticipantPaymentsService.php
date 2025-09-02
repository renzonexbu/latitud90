<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;

class GetParticipantPaymentsService
{
    /**
     * Obtener pagos de un participante
     *
     * @param Participant $participant
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function execute(Participant $participant)
    {
        return $participant->payments()->orderBy('created_at', 'desc')->get();
    }
}
