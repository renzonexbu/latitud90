<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\SalesExecutive;
use App\Models\Participant;
use App\Models\Payment;
use App\Traits\HasPermissions;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminController extends Controller
{
    use HasPermissions;
    /**
     * Mostrar el dashboard principal del administrador
     */
    public function dashboard(Request $request)
    {
        // Estadísticas generales
        $stats = [
            'total_passengers' => Participant::count(),
            'monthly_revenue' => Payment::where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('amount'),
            'active_programs' => Program::where('active', true)->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
        ];

        // Reservas recientes (placeholder no utilizado actualmente en la vista)
        $recentReservations = collect();

        // Pagos recientes
        $recentPayments = Payment::query()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Plantillas más usadas (máximo 3) basadas en cantidad de programCourses
        $mostUsedTemplates = Program::with(['programCourses.course.institution', 'programCourses.course.participants'])
            ->where('active', true)
            ->withCount('programCourses')
            ->orderBy('program_courses_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Obtener todos los ProgramCourses activos con sus relaciones
        $programCoursesQuery = ProgramCourse::with([
            'course.institution',
            'course.participants',
            'program',
            'salesExecutive'
        ])->where('active', true);

        // Filtro por ejecutivo comercial
        if ($request->filled('salesExecutiveId')) {
            $programCoursesQuery->where('sales_executive_id', $request->salesExecutiveId);
        }

        $programCourses = $programCoursesQuery->get();

        // Tabla de estado de pago por institución/programa
        $today = Carbon::today();
        $institutionsPayments = $programCourses->map(function ($programCourse) {
            $course = $programCourse->course;
            $program = $programCourse->program;
            $participants = $course?->participants ?? collect();

            // Cantidad de alumnos activos
            $activeParticipants = $participants->filter(fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled');
            $totalStudents = $activeParticipants->count();

            // Calcular el total sumando el precio final de cada participante
            $target = 0.0;
            foreach ($activeParticipants as $participant) {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $target += $priceData['final_price'] ?? 0;
            }

            // Recaudado: pagos normales + cuotas de suscripciones pagadas
            $normalPayments = (float) DB::table('payments')
                ->join('orders', 'payments.order_id', '=', 'orders.id')
                ->where('orders.program_id', $programCourse->id)
                ->whereIn('payments.status', ['approved', 'completed'])
                ->sum('payments.amount');

            $subscriptionPayments = (float) DB::table('installments')
                ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
                ->where('installment_plans.program_id', $programCourse->id)
                ->where('installments.status', 'paid')
                ->sum('installments.amount');

            $collected = $normalPayments + $subscriptionPayments;
            $percent = $target > 0 ? (int) round(($collected / $target) * 100, 0) : 0;

            $executive = $programCourse->salesExecutive;

            return [
                'id' => $programCourse->id,
                'institutionName' => optional($course?->institution)->name ?? '—',
                'programCode' => $programCourse->code ?? '—',
                'destination' => $programCourse->destination ?? $program->destination ?? '—',
                'executiveName' => $executive ? $executive->name : '—',
                'executiveId' => $executive ? $executive->id : null,
                'departureDate' => $programCourse->departure_date,
                'departureDateFormatted' => $programCourse->departure_date
                    ? Carbon::parse($programCourse->departure_date)->format('d/m/Y')
                    : '—',
                'students' => $totalStudents,
                'percent' => $percent,
                'totalCollected' => round($collected, 2),
                'targetAmount' => round((float) $target, 2),
            ];
        });

        // Ordenar: próximos a ejecutarse primero, ya ejecutados al final
        $institutionsPayments = $institutionsPayments->sortBy(function ($item) use ($today) {
            $departureDate = $item['departureDate'] ? Carbon::parse($item['departureDate']) : null;

            if (!$departureDate) {
                // Sin fecha, al final
                return [2, PHP_INT_MAX];
            }

            if ($departureDate->gte($today)) {
                // Próximos: ordenar por fecha ascendente (más cercano primero)
                return [0, $departureDate->timestamp];
            } else {
                // Ya ejecutados: ordenar por fecha descendente (más reciente primero)
                return [1, PHP_INT_MAX - $departureDate->timestamp];
            }
        })->values();

        // Lista de ejecutivos comerciales para el filtro
        $salesExecutives = SalesExecutive::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentReservations' => $recentReservations,
            'recentPayments' => $recentPayments,
            'mostUsedTemplates' => $mostUsedTemplates,
            'institutionsPayments' => $institutionsPayments,
            'salesExecutives' => $salesExecutives,
            'filters' => [
                'salesExecutiveId' => $request->salesExecutiveId ?? null,
            ],
            'permissions' => $this->getPermissionsData(),
        ]);
    }
}
