<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;

class GetShowDataService
{
    use AdminLogging;
    /**
     * Obtener datos de una plantilla de programa para mostrar
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
        // Load images through accessor
        $program->images = $program->images;

        // Log the template view
        $this->logView(
            'programs',
            'ProgramTemplate',
            $program->id,
            "Plantilla de programa consultada: {$program->name}",
            [
                'program_name' => $program->name,
                'destination' => $program->destination,
                'active' => $program->active,
            ]
        );

        return [
            'program' => $program
        ];
    }
}
