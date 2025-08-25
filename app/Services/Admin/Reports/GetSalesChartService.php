<?php

namespace App\Services\Admin\Reports;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GetSalesChartService
{
    /**
     * Obtener datos para el gráfico de ventas
     *
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function execute(Request $request)
    {
        $period = $request->period ?? 'daily';
        $dateFrom = $request->date_from ?? Carbon::now()->subMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? Carbon::now()->format('Y-m-d');

        $query = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        return $this->getDataByPeriod($query, $period);
    }

    /**
     * Obtener datos según el período especificado
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getDataByPeriod($query, string $period)
    {
        switch ($period) {
            case 'daily':
                return $query->selectRaw('DATE(created_at) as date, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();
            case 'weekly':
                return $query->selectRaw('YEARWEEK(created_at) as week, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('week')
                    ->orderBy('week')
                    ->get();
            case 'monthly':
                return $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total, COUNT(*) as count')
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();
            default:
                return collect();
        }
    }
}
