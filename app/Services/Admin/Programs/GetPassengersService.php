<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;

class GetPassengersService
{
    use AdminLogging;
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

        // Log the passengers list view
        $this->logView(
            'programs',
            'ProgramPassengers',
            $program->id,
            "Lista de pasajeros consultada para programa: {$program->name} ({$program->code})",
            [
                'program_name' => $program->name,
                'program_code' => $program->code,
                'total_passengers' => $participants->total(),
                'current_page' => $participants->currentPage(),
                'per_page' => $participants->perPage(),
            ]
        );

        return [
            'program' => $program,
            'participants' => $participants,
        ];
    }
}
