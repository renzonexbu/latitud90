<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;

class GetShowDataService
{
    /**
     * Obtener datos de un programa para mostrar
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
        $program->load(['course', 'course.participants']);

        return [
            'program' => $program
        ];
    }
}
