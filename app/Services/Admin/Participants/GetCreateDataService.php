<?php

namespace App\Services\Admin\Participants;

use App\Models\Course;
use App\Models\Institution;

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

        return [
            'courses' => $courses,
            'institutions' => $institutions
        ];
    }
}
