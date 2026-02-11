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

        // Obtener todos los cursos activos (donde program_courses.active = true)
        $coursesQuery = \App\Models\Course::with([
            'programCourses' => function($query) use ($request) {
                $query->where('active', true);
                // Filtro por ejecutivo comercial
                if ($request->filled('salesExecutiveId')) {
                    $query->where('sales_executive_id', $request->salesExecutiveId);
                }
            },
            'programCourses.program',
            'programCourses.salesExecutive',
            'institution',
            'participants'
        ])->whereHas('programCourses', function($query) use ($request) {
            $query->where('active', true);
            // Filtro por ejecutivo comercial
            if ($request->filled('salesExecutiveId')) {
                $query->where('sales_executive_id', $request->salesExecutiveId);
            }
        });

        $courses = $coursesQuery->get();

        // Usar el mismo servicio que usa CourseController para calcular métricas
        $courseDataService = new \App\Services\Admin\Courses\CourseDataService();
        $coursesWithMetrics = $courseDataService->calculateCourseMetrics($courses);

        // Tabla de estado de pago por institución/programa
        $today = Carbon::today();
        $institutionsPayments = $coursesWithMetrics->map(function ($course) use ($today) {
            $programCourse = $course->programCourses->first();
            
            if (!$programCourse) {
                return null;
            }

            $executive = $programCourse->salesExecutive;

            return [
                'id' => $programCourse->id,
                'institutionName' => optional($course->institution)->name ?? '—',
                'programCode' => $programCourse->code ?? '—',
                'destination' => $programCourse->destination ?? $programCourse->program->destination ?? '—',
                'executiveName' => $executive ? $executive->name : '—',
                'executiveId' => $executive ? $executive->id : null,
                'departureDate' => $programCourse->departure_date,
                'departureDateFormatted' => $programCourse->departure_date
                    ? Carbon::parse($programCourse->departure_date)->format('d/m/Y')
                    : '—',
                'students' => $course->total_students ?? 0,
                'percent' => $course->course_payment_percentage ?? 0,
                'totalCollected' => round($course->course_paid_amount ?? 0, 2),
                'targetAmount' => round($course->course_total_amount ?? 0, 2),
            ];
        })->filter();

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
