<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;

class GetShowDataService
{
    use AdminLogging;
    /**
     * Obtener datos de un programa para mostrar
     *
     * @param Program $program
     * @return array
     */
    public function execute(Program $program): array
    {
        $program->load(['course', 'course.participants']);

        // Log the program view
        $this->logView(
            'programs',
            'Program',
            $program->id,
            "Programa consultado: {$program->name} ({$program->code})",
            [
                'program_name' => $program->name,
                'program_code' => $program->code,
                'destination' => $program->destination,
                'has_course' => $program->course ? true : false,
                'participants_count' => $program->course?->participants?->count() ?? 0,
            ]
        );

        return [
            'program' => $program
        ];
    }
}
