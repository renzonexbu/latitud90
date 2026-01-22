<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Document;
use App\Helpers\ParticipantPriceHelper;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GetParticipantsService
{
    /**
     * Obtener participantes con filtros y datos relacionados
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        // Obtener participantes paginados
        $participants = Participant::with(['courses', 'courses.institution', 'courses.programCourses.program'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Obtener todos los participantes para los filtros (sin paginación)
        $allParticipants = Participant::with(['courses', 'courses.institution', 'courses.programCourses.program'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener dataset de inscripciones con montos pagados
        $enrollments = $this->getEnrollmentsData();

        // Obtener cursos activos
        $courses = Course::with(['programCourses.program', 'institution'])
            ->where('status', 'active')
            ->orderBy('institution_id')
            ->get();

        // Obtener instituciones activas
        $institutions = Institution::active()
            ->orderBy('name')
            ->get();

        // Obtener program_courses activos (instancias específicas de programas)
        $programs = ProgramCourse::with(['program', 'course.institution'])
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($programCourse) {
                return [
                    'id' => $programCourse->id,
                    'name' => $programCourse->name,
                    'destination' => $programCourse->program->destination ?? '',
                    'year' => $programCourse->year,
                    'departure_date' => $programCourse->departure_date?->format('d/m/Y'),
                ];
            });

        // Obtener tipos de documento
        $documentTypes = Document::orderBy('name')->get();

        return [
            'participants' => $participants,
            'allParticipants' => $allParticipants,
            'enrollments' => $enrollments,
            'courses' => $courses,
            'institutions' => $institutions,
            'programs' => $programs,
            'documentTypes' => $documentTypes,
            'filters' => $request->only(['search', 'institution', 'level', 'program', 'status', 'active'])
        ];
    }

    /**
     * Obtener datos de inscripciones con montos pagados y precios calculados
     *
     * @return \Illuminate\Support\Collection
     */
    private function getEnrollmentsData()
    {
        // Obtener datos base de inscripciones usando la nueva estructura
        $enrollmentsBase = DB::table('participants as p')
            ->leftJoin('participant_course as pc', 'pc.participant_id', '=', 'p.id')
            ->leftJoin('courses as c', 'c.id', '=', 'pc.course_id')
            ->leftJoin('program_courses as pgc', 'pgc.course_id', '=', 'c.id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pgc.program_id')
            ->leftJoin('institutions as i', 'i.id', '=', 'c.institution_id')
            ->leftJoin('participant_program as pp', function ($join) {
                $join->on('pp.participant_id', '=', 'p.id')
                    ->on('pp.program_id', '=', 'pgc.id');
            })
            ->leftJoin('orders as o', function ($join) {
                $join->on('o.participant_id', '=', 'p.id')
                    ->on('o.program_id', '=', 'pgc.id');
            })
            ->leftJoin('orders_detail as od', function ($join) {
                $join->on('od.order_id', '=', 'o.id')
                    ->where('od.is_paid', true);
            })
            ->groupBy([
                'p.id',
                'p.first_last_name',
                'p.second_last_name',
                'p.first_name',
                'p.second_name',
                'p.document_number',
                'p.document_type',
                'pp.is_active',
                'pc.id',
                'pc.individual_price',
                'pc.status',
                'pgc.id',
                'pr.id',
                'pgc.code',
                'pgc.name',
                'pr.destination',
                'pgc.year',
                'c.education_level',
                'c.course_number',
                'i.name',
            ])
            ->select([
                'p.id as participant_id',
                'p.first_last_name',
                'p.second_last_name',
                'p.first_name',
                'p.second_name',
                'p.document_number',
                'p.document_type',
                DB::raw('COALESCE(pp.is_active, 1) as is_active'),
                'pc.id as participant_course_id',
                'pc.individual_price',
                'pc.status as enrollment_status',
                'pgc.id as program_course_id',
                'pr.id as program_id',
                'pgc.code as program_code',
                'pgc.name as program_name',
                'pr.destination as program_destination',
                'pgc.year as program_year',
                'c.education_level',
                'c.course_number',
                'i.name as institution_name',
                DB::raw('0 as paid_amount'),
            ])
            ->orderByDesc('pc.created_at')
            ->get();

        // Calcular precios finales con descuentos usando el helper
        return $enrollmentsBase->map(function ($enrollment) {
            $participant = Participant::find($enrollment->participant_id);
            $programCourse = ProgramCourse::find($enrollment->program_course_id);

            if ($participant && $programCourse) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $enrollment->total_due = $priceData['final_price'];

                // Calcular monto pagado correctamente (incluyendo reembolsos como negativos)
                $paidAmount = Payment::whereHas('order', function($q) use ($enrollment) {
                        $q->where('participant_id', $enrollment->participant_id)
                          ->where('program_id', $enrollment->program_course_id);
                    })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');

                $enrollment->paid_amount = round($paidAmount, 2);
            } else {
                $enrollment->total_due = $enrollment->individual_price ?? 0;
                $enrollment->paid_amount = 0;
            }

            return $enrollment;
        });
    }
}
