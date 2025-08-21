<?php

namespace App\Http\Controllers;

use App\Services\EcommerceAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(EcommerceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Mostrar dashboard principal con análisis del funnel
     */
    public function index(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Cachear resultados por 5 minutos para mejorar performance
        $cacheKey = "funnel_analysis_{$dateFrom}_{$dateTo}";
        $analysis = Cache::remember($cacheKey, 300, function () use ($dateFrom, $dateTo) {
            return $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);
        });

        return view('dashboard.index', compact('analysis', 'dateFrom', 'dateTo'));
    }

    /**
     * Obtener datos del dashboard via AJAX
     */
    public function getData(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $analysis = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);

        return response()->json($analysis);
    }

    /**
     * Exportar reporte consolidado
     */
    public function exportReport(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'format' => 'required|in:excel,csv,pdf',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $format = $request->input('format');

        $analysis = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);

        // Aquí implementarías la lógica de exportación según el formato
        // Por ahora solo retornamos los datos
        return response()->json([
            'success' => true,
            'message' => "Reporte exportado en formato {$format}",
            'data' => $analysis,
        ]);
    }
}
