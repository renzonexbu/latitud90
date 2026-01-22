<?php

namespace App\Services\Admin\Participants;

use App\Models\Course;
use App\Models\Institution;
use App\Models\ProgramCourse;

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

        // Obtener program_courses activos (cursos específicos/planes de programas)
        $programs = ProgramCourse::with(['program', 'course.institution'])
            ->where('active', true)
            ->orderBy('departure_date', 'desc')
            ->get()
            ->map(function ($programCourse) {
                return [
                    'id' => $programCourse->id,
                    'name' => $programCourse->name ?? $programCourse->program->name ?? 'Sin nombre',
                    'destination' => $programCourse->destination ?? $programCourse->program->destination ?? 'Sin destino',
                    'year' => $programCourse->course->year ?? date('Y'),
                    'departure_date' => $programCourse->departure_date,
                ];
            });


        return [
            'courses' => $courses,
            'institutions' => $institutions,
            'programs' => $programs
        ];
    }
}
