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

            // Calcular métricas agregadas de pago a nivel curso
            $participants = $course->participants ?? collect();
            $activeParticipants = $participants->filter(function ($p) {
                return ($p->pivot->status ?? 'active') !== 'cancelled';
            });

            $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($course) {
                $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? ($course->program->trip_price ?? 0));
                $adj = (float) ($p->pivot->price_adjustments ?? 0);
                return $carry + round($base + $adj, 2);
            }, 0.0);

            $coursePaidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($course) {
                    $q->where('program_id', $course->program->id);
                })
                ->where('status', 'approved')
                ->sum('amount');
            $coursePaidAmount = round($coursePaidAmount, 2);

            $coursePaymentPercentage = $courseTotalAmount > 0
                ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
                : 0;

            // Adjuntar para el card individual
            $course->program->payment_percentage = $coursePaymentPercentage;
            $course->program->paid_amount = $coursePaidAmount;
            $course->program->total_amount = $courseTotalAmount > 0 ? $courseTotalAmount : ($course->program->trip_price ?? 0);
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
