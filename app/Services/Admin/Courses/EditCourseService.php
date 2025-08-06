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
            'grade' => $this->getOrdinalGrade($course->grade),
            'shift' => $course->shift,
            'education_level' => $course->education_level,
        ];
    }

    private function getOrdinalGrade($grade): string
    {
        if (!$grade) return 'N/A';

        $suffixes = [
            1 => 'ro',
            2 => 'do', 
            3 => 'ro',
            4 => 'to',
            5 => 'to',
            6 => 'to',
            7 => 'mo',
            8 => 'vo',
            9 => 'no',
            10 => 'mo',
            11 => 'mo',
            12 => 'mo'
        ];

        $suffix = $suffixes[$grade] ?? 'mo';
        return $grade . $suffix;
    }
}
