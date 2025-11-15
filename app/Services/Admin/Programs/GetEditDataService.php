<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;

class GetEditDataService
{
    use AdminLogging;
    /**
     * Obtener datos necesarios para editar una plantilla de programa
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
        // Load images through accessor
        $program->images = $program->images;

        // Log the template edit view
        $this->logView(
            'programs',
            'ProgramTemplate',
            $program->id,
            "Plantilla de programa abierta para edición: {$program->name}",
            [
                'program_name' => $program->name,
                'destination' => $program->destination,
                'active' => $program->active,
            ]
        );

        return [
            'program' => $program,
        ];
    }
}
