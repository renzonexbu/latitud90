<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
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
        $participants = Participant::with(['courses', 'courses.institution', 'courses.program'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Obtener todos los participantes para los filtros (sin paginación)
        $allParticipants = Participant::with(['courses', 'courses.institution', 'courses.program'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener dataset de inscripciones con montos pagados
        $enrollments = $this->getEnrollmentsData();

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
        // Obtener datos base de inscripciones
        $enrollmentsBase = DB::table('participants as p')
            ->leftJoin('participant_program as pp', 'pp.participant_id', '=', 'p.id')
            ->leftJoin('programs as pr', 'pr.id', '=', 'pp.program_id')
            ->leftJoin('courses as c', 'c.program_id', '=', 'pr.id')
            ->leftJoin('institutions as i', 'i.id', '=', 'c.institution_id')
            ->leftJoin('orders as o', function ($join) {
                $join->on('o.participant_id', '=', 'p.id')
                    ->on('o.program_id', '=', 'pr.id');
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
                'p.is_active',
                'pp.id',
                'pp.enrollment_code',
                'pp.individual_price',
                'pp.status',
                'pr.id',
                'pr.code',
                'pr.name',
                'pr.destination',
                'pr.year',
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
                'p.is_active',
                'pp.id as participant_program_id',
                'pp.enrollment_code',
                'pp.individual_price',
                'pp.status as enrollment_status',
                'pr.id as program_id',
                'pr.code as program_code',
                'pr.name as program_name',
                'pr.destination as program_destination',
                'pr.year as program_year',
                'c.education_level',
                'c.course_number',
                'i.name as institution_name',
                DB::raw('0 as paid_amount'),
            ])
            ->orderByDesc('pp.created_at')
            ->get();

        // Calcular precios finales con descuentos usando el helper
        return $enrollmentsBase->map(function ($enrollment) {
            $participant = Participant::find($enrollment->participant_id);
            $program = Program::find($enrollment->program_id);

            if ($participant && $program) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
                $enrollment->total_due = $priceData['final_price'];
                
                // Calcular monto pagado correctamente (incluyendo reembolsos como negativos)
                $paidAmount = Payment::whereHas('order', function($q) use ($enrollment) {
                        $q->where('participant_id', $enrollment->participant_id)
                          ->where('program_id', $enrollment->program_id);
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
