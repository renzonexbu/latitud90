<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = AdminLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filtros
        if ($request->filled('module')) {
            $query->byModule($request->module);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $logs = $query->paginate(50);

        // Obtener estadísticas
        $stats = [
            'total_logs' => AdminLog::count(),
            'today_logs' => AdminLog::whereDate('created_at', today())->count(),
            'modules' => AdminLog::select('module')->distinct()->pluck('module'),
            'actions' => AdminLog::select('action')->distinct()->pluck('action'),
        ];

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => $request->only(['module', 'action', 'user_id', 'date_from', 'date_to']),
        ]);
    }

    public function show($id)
    {
        $log = AdminLog::with('user')->findOrFail($id);

        return Inertia::render('Admin/Logs/Show', [
            'log' => $log,
        ]);
    }

    public function export(Request $request)
    {
        $query = AdminLog::with('user');

        // Aplicar filtros
        if ($request->filled('module')) {
            $query->byModule($request->module);
        }

        if ($request->filled('action')) {
            $query->byAction($request->action);
        }

        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->byDateRange($request->date_from, $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        // Transformar datos para exportación
        $exportData = $logs->map(function ($log) {
            return [
                'Fecha' => $log->created_at->format('d/m/Y H:i:s'),
                'Usuario' => $log->user_name ?? 'N/A',
                'Email' => $log->user_email ?? 'N/A',
                'Acción' => $log->action,
                'Módulo' => $log->module,
                'Recurso' => $log->resource_type,
                'ID Recurso' => $log->resource_id,
                'Descripción' => $log->description,
                'IP' => $log->ip_address,
                'User Agent' => $log->user_agent,
                'Método' => $log->request_method,
                'URL' => $log->request_url,
            ];
        });

        $format = $request->get('format', 'csv');
        $filename = 'admin_logs_' . now()->format('Y-m-d_H-i-s');

        if ($format === 'csv') {
            return $this->exportToCsv($exportData, $filename);
        } else {
            return $this->exportToExcel($exportData, $filename);
        }
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
