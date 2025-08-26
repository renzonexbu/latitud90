<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Support\Facades\Log;

class ToggleStatusService
{
    use AdminLogging;
    /**
     * Cambiar el estado activo/inactivo de un programa
     *
     * @param Program $program
     * @return Program
     */
    public function execute(Program $program): Program
    {
        $oldStatus = $program->active;
        $newStatus = !$program->active;
        $program->update(['active' => $newStatus]);

        // Log the status change
        $this->logStatusChange(
            'programs',
            'Program',
            $program->id,
            $oldStatus ? 'active' : 'inactive',
            $newStatus ? 'active' : 'inactive',
            "Estado del programa cambiado: {$program->name} ({$program->code})",
            [
                'program_name' => $program->name,
                'program_code' => $program->code,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]
        );

        Log::info('Estado del programa actualizado', [
            'program_id' => $program->id,
            'program_name' => $program->name,
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);

        return $program;
    }
}
