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

        // Listado mínimo para filtros frontend (sin relaciones pesadas)
        $allParticipants = Participant::select('id', 'first_name', 'second_name', 'first_last_name', 'second_last_name', 'document_number', 'document_type', 'is_active')
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
                    'code' => $programCourse->code ?? '',
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
                'pp.enrollment_code',
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
                'pp.enrollment_code',
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

        // Pre-cargar totales de pagos por (participant_id, program_id) en UNA sola query
        $pairs = $enrollmentsBase
            ->filter(fn($e) => $e->participant_id && $e->program_course_id)
            ->map(fn($e) => [(int) $e->participant_id, (int) $e->program_course_id])
            ->unique(fn($p) => $p[0] . '_' . $p[1])
            ->values();

        $paidMap = [];       // key: "pid_pgid" → total_paid
        $contributionMap = []; // key: "pid_pgid" → aportes AP

        // Pre-cargar modelos en batch para evitar N+1 en find()
        $participantsById = collect();
        $programCoursesById = collect();

        if ($pairs->isNotEmpty()) {
            $participantIds = $pairs->pluck(0)->unique()->values()->all();
            $programIds = $pairs->pluck(1)->unique()->values()->all();

            $participantsById = \App\Models\Participant::whereIn('id', $participantIds)->get()->keyBy('id');
            $programCoursesById = \App\Models\ProgramCourse::with('course')->whereIn('id', $programIds)->get()->keyBy('id');

            // Total pagado agregado en 1 query
            $paidRows = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->whereIn('orders.participant_id', $participantIds)
                ->whereIn('orders.program_id', $programIds)
                ->whereIn('payments.status', ['approved', 'completed'])
                ->selectRaw('orders.participant_id as pid, orders.program_id as pgid, SUM(payments.amount) as total')
                ->groupBy('orders.participant_id', 'orders.program_id')
                ->get();
            foreach ($paidRows as $r) {
                $paidMap[$r->pid . '_' . $r->pgid] = (float) $r->total;
            }

            // Aportes (AP) en 1 query
            $contributionRows = DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->join('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
                ->whereIn('orders.participant_id', $participantIds)
                ->whereIn('orders.program_id', $programIds)
                ->whereIn('payments.status', ['approved', 'completed'])
                ->where('payment_options.report_code', 'AP')
                ->selectRaw('orders.participant_id as pid, orders.program_id as pgid, SUM(payments.amount) as total')
                ->groupBy('orders.participant_id', 'orders.program_id')
                ->get();
            foreach ($contributionRows as $r) {
                $contributionMap[$r->pid . '_' . $r->pgid] = (float) $r->total;
            }
        }

        // Calcular precios usando el helper con modelos ya cargados en memoria
        return $enrollmentsBase->map(function ($enrollment) use ($paidMap, $contributionMap, $participantsById, $programCoursesById) {
            if (!$enrollment->participant_id || !$enrollment->program_course_id) {
                return $enrollment;
            }

            $key = $enrollment->participant_id . '_' . $enrollment->program_course_id;
            $totalPaid = $paidMap[$key] ?? 0.0;

            $participant = $participantsById->get($enrollment->participant_id);
            $programCourse = $programCoursesById->get($enrollment->program_course_id);

            if ($participant && $programCourse) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $basePrice = (float) ($priceData['base_price'] ?? 0);
                $regularDiscounts = (float) ($priceData['regular_discounts'] ?? 0);
                $releasedDiscounts = (float) ($priceData['released_discounts'] ?? 0);
                $netAmount = (float) ($priceData['final_price'] ?? 0);

                // Si está de baja, capear precio al abono
                if ($enrollment->is_active == 0) {
                    $netAmount = min($netAmount, max($totalPaid, 0));
                }

                $enrollment->base_price = $basePrice;
                $enrollment->discounts = $regularDiscounts;
                $enrollment->total_due = $netAmount;
                $enrollment->paid_amount = $totalPaid;
                $enrollment->balance = max($netAmount - $totalPaid, 0);
                $enrollment->payment_percentage = $netAmount > 0 ? round(($totalPaid / $netAmount) * 100, 2) : 0;
                $enrollment->released_amount = round($releasedDiscounts, 2);
            } else {
                $enrollment->base_price = 0;
                $enrollment->discounts = 0;
                $enrollment->total_due = 0;
                $enrollment->paid_amount = $totalPaid;
                $enrollment->balance = 0;
                $enrollment->payment_percentage = 0;
                $enrollment->released_amount = 0;
            }

            $enrollment->contribution = round($contributionMap[$key] ?? 0.0, 2);

            return $enrollment;
        });
    }
}
