<?php

namespace App\Services\Admin\Participants;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;

class GetCreateDataService
{
    /**
     * Obtener datos necesarios para el formulario de creación de participantes
     *
     * @return array
     */
    public function execute(): array
    {
        // Obtener cursos activos
        $courses = Course::with(['program', 'institution'])
            ->where('status', 'active')
            ->orderBy('institution_id')
            ->get();

        // Obtener instituciones activas
        $institutions = Institution::active()
            ->orderBy('name')
            ->get();

        // Obtener programas activos o con status 'reserva' (programas futuros)
        $programs = Program::with(['course.institution'])
            ->where(function($query) {
                $query->where('active', true)
                      ->orWhere('status', 'reserva');
            })
            ->orderBy('name')
            ->get();


        return [
            'courses' => $courses,
            'institutions' => $institutions,
            'programs' => $programs
        ];
    }
}
