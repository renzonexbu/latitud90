<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Participant;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminController extends Controller
{
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

        // Programas activos (máximo 3) con relaciones necesarias
        // Nota: 'images' es un accessor (no relación), no se usa en with()
        $activePrograms = Program::with(['course.institution', 'course.participants'])
            ->where('active', true)
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        // Tabla de estado de pago por institución/programa (mock básico con datos del modelo)
        $institutionsPayments = $activePrograms->map(function ($program) {
            $course = $program->course;
            $collected = (float) ($course->collected_amount ?? 0);
            $target = (float) ($course->target_amount ?? $program->trip_price ?? 0);
            $percent = $target > 0 ? (int) round(($collected / $target) * 100, 0) : 0;
            return [
                'institutionName' => optional($course->institution)->name ?? '—',
                'educationLevel' => $course->education_level ?? '—',
                'course' => $course->course_number ?? '—',
                'year' => optional($course)->year ?? Carbon::now()->year,
                'programName' => $program->name,
                'destination' => $program->destination,
                'students' => $course->total_students ?? ($course->participants?->count() ?? 0),
                'percent' => $percent,
                'totalCollected' => $collected,
                'targetAmount' => $target,
            ];
        });
        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentReservations' => $recentReservations,
            'recentPayments' => $recentPayments,
            'activePrograms' => $activePrograms,
            'institutionsPayments' => $institutionsPayments,
        ]);
    }
}
