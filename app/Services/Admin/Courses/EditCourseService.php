<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;

class EditCourseService
{
    public function execute(int $courseId): array
    {
        $course = Course::with(['institution', 'program', 'participants'])
            ->findOrFail($courseId);

        // Asegurar que los programas se carguen con todos los campos necesarios
                            if ($course->program) {
                        $course->program->makeVisible(['trip_price', 'name', 'destination']);
                        
                        // Agregar campos calculados para compatibilidad con el card
                        $course->program->payment_percentage = 0; // Por ahora 0, se puede calcular después
                        $course->program->paid_amount = 0; // Por ahora 0, se puede calcular después
                        $course->program->total_amount = $course->program->trip_price;
                    }

        // Agregar los accessors calculados
        $course->append(['payment_percentage', 'payment_percentage_text']);

        return [
            'course' => $course,
            'institution' => $course->institution,
            'program' => $course->program,
            'participants' => $course->participants,
        ];
    }

    public function getCourseHeaderInfo(Course $course): array
    {
        return [
            'institution_name' => $course->institution?->name ?? 'Sin institución',
            'year' => $course->year,
            'grade' => $course->course_display,
            'shift' => null,
            'education_level' => $course->education_level,
        ];
    }

    // Ya no se usa ordinal; el modelo expone course_display
}
