<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;

class GetPassengersService
{
    /**
     * Obtener participantes de un programa
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
        $participants = $program->participants()
            ->with(['payments'])
            ->where('status', '!=', 'cancelled')
            ->paginate(10);

        return [
            'program' => $program,
            'participants' => $participants,
        ];
    }
}
