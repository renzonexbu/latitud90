<?php

namespace App\Services\Admin\SuperAdmin\SystemLogs;

use App\Models\AdminLog;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;

class GetAdminLogsService
{
    use AdminLogging;

    public function execute(Request $request)
    {
        $query = AdminLog::query();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('method')) {
            $query->where('request_method', $request->method);
        }

        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            $now = now();
            
            switch ($dateRange) {
                case 'today':
                    $query->whereDate('created_at', $now->toDateString());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [$now->startOfWeek(), $now->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year);
                    break;
                case 'quarter':
                    $query->whereBetween('created_at', [$now->startOfQuarter(), $now->endOfQuarter()]);
                    break;
                case 'year':
                    $query->whereYear('created_at', $now->year);
                    break;
            }
        }

        // Ordenar por fecha más reciente
        $query->orderBy('created_at', 'desc');

        $perPage = 15;
        $logs = $query->paginate($perPage);

        // Log the view
        $this->logView(
            'admin_logs',
            'admin_logs',
            0,
            "Vista de logs del sistema consultada"
        );

        return [
            'adminLogs' => $logs->items(),
            'filters' => $request->only(['search', 'action', 'module', 'method', 'date_range']),
            'currentPage' => $logs->currentPage(),
            'totalLogs' => $logs->total(),
            'logsPerPage' => $perPage,
        ];
    }
}
