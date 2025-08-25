<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use Illuminate\Support\Facades\Log;

class ToggleStatusService
{
    /**
     * Cambiar el estado activo/inactivo de un programa
     *
     * @param Program $program
     * @return Program
     */
    public function execute(Program $program): Program
    {
        $oldStatus = $program->active;
        $program->update(['active' => !$program->active]);

        Log::info('Estado del programa actualizado', [
            'program_id' => $program->id,
            'program_name' => $program->name,
            'old_status' => $oldStatus,
            'new_status' => $program->active
        ]);

        return $program;
    }
}
