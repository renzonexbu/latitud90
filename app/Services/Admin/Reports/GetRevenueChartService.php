<?php

namespace App\Services\Admin\Reports;

use App\Models\Payment;
use App\Traits\AdminLogging;
use Carbon\Carbon;
use Illuminate\Http\Request;

class GetRevenueChartService
{
    use AdminLogging;
    /**
     * Obtener datos para el gráfico de ingresos
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $filters = $request->only(['period', 'dateFrom', 'dateTo']);

        $period = $filters['period'] ?? 'daily';
        $dateFrom = $filters['dateFrom'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['dateTo'] ?? Carbon::now()->format('Y-m-d');

        // Generar datos de ingresos por período
        $revenueData = $this->generateRevenueData($period, $dateFrom, $dateTo);

        // Datos de métodos de pago
        $paymentMethodsData = Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->join('payment_gateways', 'payments.payment_gateway_id', '=', 'payment_gateways.id')
            ->selectRaw('payment_gateways.name as method, SUM(payments.amount) as amount')
            ->groupBy('payment_gateway_id', 'payment_gateways.name')
            ->get();

        // Log the revenue chart generation
        $this->logExport(
            'reports',
            "Gráfico de ingresos generado: {$period} - {$dateFrom} a {$dateTo}",
            [
                'period' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'filters' => $filters,
                'data_points' => count($revenueData),
                'payment_methods_count' => $paymentMethodsData->count(),
                'report_type' => 'revenue_chart',
            ]
        );

        return [
            'revenueData' => $revenueData,
            'paymentMethodsData' => $paymentMethodsData,
            'filters' => $filters
        ];
    }

    /**
     * Generar datos de ingresos por período
     *
     * @param string $period
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    private function generateRevenueData(string $period, string $dateFrom, string $dateTo): array
    {
        $data = [];
        $startDate = Carbon::parse($dateFrom);
        $endDate = Carbon::parse($dateTo);

        while ($startDate <= $endDate) {
            $date = $startDate->format('Y-m-d');

            $revenue = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->sum('amount');

            $transactions = Payment::where('status', 'completed')
                ->whereDate('created_at', $date)
                ->count();

            $data[] = [
                'date' => $startDate->format('d/m/Y'),
                'revenue' => $revenue,
                'transactions' => $transactions
            ];

            if ($period === 'daily') {
                $startDate->addDay();
            } elseif ($period === 'weekly') {
                $startDate->addWeek();
            } else {
                $startDate->addMonth();
            }
        }

        return $data;
    }
}
