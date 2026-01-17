<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Participant;
use App\Models\Payment;
use App\Traits\HasPermissions;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminController extends Controller
{
    use HasPermissions;
    /**
     * Mostrar el dashboard principal del administrador
     */
    public function dashboard()
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
        // Incluir el conteo de programCourses para mostrar
        $mostUsedTemplates = Program::with(['programCourses.course.institution', 'programCourses.course.participants'])
            ->where('active', true)
            ->withCount('programCourses')
            ->orderBy('program_courses_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Tabla de estado de pago por institución/programa basada en datos reales
        $institutionsPayments = $mostUsedTemplates->flatMap(function ($program) {
            // Iterar sobre cada ProgramCourse del programa
            return $program->programCourses->map(function ($programCourse) use ($program) {
                $course = $programCourse->course;
                $participants = $course?->participants ?? collect();

                // Cantidad de alumnos activos (misma lógica que CourseDataService)
                $activeParticipants = $participants->filter(fn($p) => ($p->pivot->status ?? 'active') !== 'cancelled');
                $totalStudents = $activeParticipants->count();

                // Objetivo: precio del programa × cantidad de alumnos activos
                $tripPrice = (float) ($programCourse->trip_price ?? 0);
                $target = $tripPrice * $totalStudents;

                // Recaudado: pagos normales + cuotas de suscripciones pagadas
                // 1. Pagos normales completados
                $normalPayments = (float) \Illuminate\Support\Facades\DB::table('payments')
                    ->join('orders', 'payments.order_id', '=', 'orders.id')
                    ->where('orders.program_id', $programCourse->id)
                    ->whereIn('payments.status', ['approved', 'completed'])
                    ->sum('payments.amount');

                // 2. Cuotas de suscripciones pagadas (installments)
                // Usar status = 'paid' porque is_paid puede no estar sincronizado
                $subscriptionPayments = (float) \Illuminate\Support\Facades\DB::table('installments')
                    ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
                    ->where('installment_plans.program_id', $programCourse->id)
                    ->where('installments.status', 'paid')
                    ->sum('installments.amount');

                $collected = $normalPayments + $subscriptionPayments;

                $percent = $target > 0 ? (int) round(($collected / $target) * 100, 0) : 0;

                return [
                    'institutionName' => optional($course->institution)->name ?? '—',
                    'educationLevel' => $course->education_level ?? '—',
                    'course' => $course->course_number ?? '—',
                    'year' => $programCourse->year ?? Carbon::now()->year,
                    'programName' => ($programCourse->code ?? '') . ' - ' . ($programCourse->name ?? $program->name),
                    'destination' => $program->destination,
                    'students' => $totalStudents,
                    'percent' => $percent,
                    'totalCollected' => round($collected, 2),
                    'targetAmount' => round((float) $target, 2),
                ];
            });
        });
        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentReservations' => $recentReservations,
            'recentPayments' => $recentPayments,
            'mostUsedTemplates' => $mostUsedTemplates,
            'institutionsPayments' => $institutionsPayments,
            'permissions' => $this->getPermissionsData(),
        ]);
    }
}
