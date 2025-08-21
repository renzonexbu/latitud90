<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EcommerceAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportsController extends Controller
{
    protected $analyticsService;

    public function __construct(EcommerceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        // Obtener fechas de filtro
        $dateFrom = $request->input('dateFrom', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('dateTo', now()->format('Y-m-d'));
        
        // Obtener análisis del funnel desde el nuevo servicio
        $funnelAnalysis = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);
        
        // Obtener datos para las gráficas específicas
        $ecommerceData = $this->getEcommerceChartData($dateFrom, $dateTo);
        
        // Obtener programas para el filtro
        $programs = \App\Models\Program::select('id', 'name')->get();
        
        // Obtener resumen general (mantener compatibilidad con el código existente)
        $summary = $this->getGeneralSummary($dateFrom, $dateTo);
        
        return Inertia::render('Admin/Reports/Index', [
            'summary' => $summary,
            'programs' => $programs,
            'ecommerceData' => $ecommerceData,
            'funnelAnalysis' => $funnelAnalysis,
        ]);
    }

    /**
     * Obtener datos específicos para las gráficas de ecommerce
     */
    private function getEcommerceChartData($dateFrom, $dateTo)
    {
        // 1. Programas más vistos (barras horizontales)
        $programViews = DB::table('ecommerce_analytics')
            ->select('program_id', 'program_name', 
                    DB::raw('COUNT(*) as views'),
                    DB::raw('COUNT(CASE WHEN payment_completed_at IS NOT NULL THEN 1 END) as conversions'))
            ->whereNotNull('program_detail_view_at')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('program_id', 'program_name')
            ->orderBy('views', 'desc')
            ->limit(10)
            ->get();

        // 2. Participantes más buscados (barras horizontales)
        $frequentParticipants = DB::table('ecommerce_analytics')
            ->select('participant_rut', 
                    DB::raw('COUNT(*) as search_count'))
            ->whereNotNull('hero_search_at')
            ->whereNotNull('participant_rut')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('participant_rut')
            ->orderBy('search_count', 'desc')
            ->limit(8)
            ->get();

        // 3. Métodos de pago vs conversión (barras agrupadas)
        $paymentMethods = DB::table('ecommerce_analytics')
            ->select('payment_method',
                    DB::raw('COUNT(*) as total'),
                    DB::raw('COUNT(CASE WHEN payment_completed_at IS NOT NULL THEN 1 END) as successful'),
                    DB::raw('ROUND((COUNT(CASE WHEN payment_completed_at IS NOT NULL THEN 1 END) / COUNT(*)) * 100, 1) as success_rate'))
            ->whereNotNull('payment_initiated_at')
            ->whereNotNull('payment_method')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();

        // 4. Análisis de cuotas (gráfico de barras)
        $installmentsAnalysis = DB::table('ecommerce_analytics')
            ->select('installments_count',
                    DB::raw('COUNT(*) as count'))
            ->whereNotNull('installments_count')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('installments_count')
            ->orderBy('installments_count')
            ->get();

        // 5. Distribución por tipo de pago (gráfico de pastel)
        $paymentTypes = DB::table('ecommerce_analytics')
            ->select('payment_type',
                    DB::raw('COUNT(*) as count'))
            ->whereNotNull('payment_type')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('payment_type')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'programViews' => $programViews,
            'frequentParticipants' => $frequentParticipants,
            'paymentMethods' => $paymentMethods,
            'installmentsAnalysis' => $installmentsAnalysis,
            'paymentTypes' => $paymentTypes,
        ];
    }

    /**
     * Obtener resumen general (mantener compatibilidad)
     */
    private function getGeneralSummary($dateFrom, $dateTo)
    {
        // Pagos diarios
        $dailyPayments = DB::table('ecommerce_analytics')
            ->select(
                DB::raw('COUNT(*) as totalPayments'),
                DB::raw('COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as paymentsToday'),
                DB::raw('SUM(CASE WHEN payment_amount IS NOT NULL THEN payment_amount ELSE 0 END) as totalAmount'),
                DB::raw('AVG(CASE WHEN payment_amount IS NOT NULL THEN payment_amount ELSE NULL END) as averageAmount')
            )
            ->whereNotNull('payment_completed_at')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->first();

        // Consolidado de pagos
        $consolidatedPayments = DB::table('ecommerce_analytics')
            ->select(
                DB::raw('COUNT(DISTINCT program_id) as totalPrograms'),
                DB::raw('COUNT(*) as totalTransactions'),
                DB::raw('SUM(CASE WHEN payment_amount IS NOT NULL THEN payment_amount ELSE 0 END) as totalAmount'),
                DB::raw('AVG(CASE WHEN payment_amount IS NOT NULL THEN payment_amount ELSE NULL END) as averagePerProgram')
            )
            ->whereNotNull('payment_completed_at')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->first();

        // Top programas por ingresos
        $topPrograms = DB::table('ecommerce_analytics')
            ->select('program_id', 'program_name',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(payment_amount) as amount'))
            ->whereNotNull('payment_completed_at')
            ->whereNotNull('program_id')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('program_id', 'program_name')
            ->orderBy('amount', 'desc')
            ->limit(5)
            ->get();

        // Top métodos de pago
        $topPaymentMethods = DB::table('ecommerce_analytics')
            ->select('payment_method as gateway',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(payment_amount) as amount'))
            ->whereNotNull('payment_completed_at')
            ->whereNotNull('payment_method')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('payment_method')
            ->orderBy('amount', 'desc')
            ->get();

        return [
            'dailyPayments' => [
                'totalPayments' => $dailyPayments->totalPayments ?? 0,
                'paymentsToday' => $dailyPayments->paymentsToday ?? 0,
                'totalAmount' => $dailyPayments->totalAmount ?? 0,
                'averageAmount' => $dailyPayments->averageAmount ?? 0,
                'topPaymentMethods' => $topPaymentMethods,
            ],
            'consolidatedPayments' => [
                'totalPrograms' => $consolidatedPayments->totalPrograms ?? 0,
                'totalTransactions' => $consolidatedPayments->totalTransactions ?? 0,
                'totalAmount' => $consolidatedPayments->totalAmount ?? 0,
                'averagePerProgram' => $consolidatedPayments->averagePerProgram ?? 0,
                'topPrograms' => $topPrograms,
            ],
            'paymentSchedule' => [
                'totalScheduled' => 0, // Implementar si es necesario
                'totalPaid' => $dailyPayments->totalPayments ?? 0,
                'totalPending' => 0, // Implementar si es necesario
                'completionRate' => 100, // Por defecto
            ],
            'partialAccount' => [
                'totalParticipants' => DB::table('ecommerce_analytics')
                    ->whereNotNull('participant_rut')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->distinct('participant_rut')
                    ->count('participant_rut'),
                'activeParticipants' => DB::table('ecommerce_analytics')
                    ->whereNotNull('payment_completed_at')
                    ->whereBetween('created_at', [$dateFrom, $dateTo])
                    ->distinct('participant_rut')
                    ->count('participant_rut'),
                'totalBalance' => 0, // Implementar si es necesario
                'averageBalance' => 0, // Implementar si es necesario
            ],
        ];
    }

    // ... otros métodos existentes ...
}
