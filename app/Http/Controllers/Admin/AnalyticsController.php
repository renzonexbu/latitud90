<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EcommerceAnalyticsService;
use App\Models\EcommerceAnalytics;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(EcommerceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        // Obtener estadísticas del funnel
        $funnelStats = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);

        // Obtener datos para gráficos
        $dailyStats = $this->getDailyStats($dateFrom, $dateTo);
        $programStats = $this->getProgramStats($dateFrom, $dateTo);
        $paymentMethodStats = $this->getPaymentMethodStats($dateFrom, $dateTo);

        return Inertia::render('Admin/Analytics/Index', [
            'funnelStats' => $funnelStats,
            'dailyStats' => $dailyStats,
            'programStats' => $programStats,
            'paymentMethodStats' => $paymentMethodStats,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function funnel(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));

        $funnelStats = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);

        return Inertia::render('Admin/Analytics/Funnel', [
            'funnelStats' => $funnelStats,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        $format = $request->get('format', 'xlsx');

        $analytics = EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->with(['participant', 'program', 'order'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Transformar datos para exportación
        $exportData = $analytics->map(function ($item) {
            return [
                'Fecha Creación' => $item->created_at->format('d/m/Y H:i:s'),
                'Session ID' => $item->session_id,
                'Participante RUT' => $item->participant_rut,
                'Programa' => $item->program_name,
                'Búsqueda participantes' => $item->hero_search_at?->format('d/m/Y H:i:s'),
                'Listado programas' => $item->program_list_view_at?->format('d/m/Y H:i:s'),
                'Detalle programa' => $item->program_detail_view_at?->format('d/m/Y H:i:s'),
                'Datos comprador' => $item->payment_details_view_at?->format('d/m/Y H:i:s'),
                'Confirmación pago' => $item->confirmation_view_at?->format('d/m/Y H:i:s'),
                'Pago Iniciado' => $item->payment_initiated_at?->format('d/m/Y H:i:s'),
                'Pago Completado' => $item->payment_completed_at?->format('d/m/Y H:i:s'),
                'Pago Fallido' => $item->payment_failed_at?->format('d/m/Y H:i:s'),
                'Monto' => $item->payment_amount,
                'Método Pago' => $item->payment_method,
                'Estado Pago' => $item->payment_status,
                'N° Orden' => $item->order_number,
                'IP' => $item->ip_address,
                'Referrer' => $item->referrer,
            ];
        });

        if ($format === 'csv') {
            return $this->exportToCsv($exportData, 'analytics_' . $dateFrom . '_' . $dateTo);
        } else {
            return $this->exportToExcel($exportData, 'analytics_' . $dateFrom . '_' . $dateTo);
        }
    }

    private function getDailyStats($dateFrom, $dateTo)
    {
        return EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total_visits')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'total_visits' => $item->total_visits,
                ];
            });
    }

    private function getProgramStats($dateFrom, $dateTo)
    {
        return EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('program_name')
            ->selectRaw('program_name, COUNT(*) as views, COUNT(payment_completed_at) as conversions')
            ->groupBy('program_name')
            ->orderByDesc('views')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'program_name' => $item->program_name,
                    'views' => $item->views,
                    'conversions' => $item->conversions,
                    'conversion_rate' => $item->views > 0 ? ($item->conversions / $item->views) * 100 : 0,
                ];
            });
    }

    private function getPaymentMethodStats($dateFrom, $dateTo)
    {
        return EcommerceAnalytics::whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('payment_method')
            ->selectRaw('payment_method, COUNT(*) as total, COUNT(CASE WHEN payment_status = "completed" THEN 1 END) as successful')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) {
                return [
                    'payment_method' => $item->payment_method,
                    'total' => $item->total,
                    'successful' => $item->successful,
                    'success_rate' => $item->total > 0 ? ($item->successful / $item->total) * 100 : 0,
                ];
            });
    }

    private function exportToCsv($data, $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');
            
            // Headers
            if ($data->count() > 0) {
                fputcsv($file, array_keys($data->first()));
            }
            
            // Data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($data, $filename)
    {
        // Implementar exportación a Excel usando PhpSpreadsheet o similar
        // Por ahora, redirigir a CSV
        return $this->exportToCsv($data, $filename);
    }
}
