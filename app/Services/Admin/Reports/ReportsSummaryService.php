<?php

namespace App\Services\Admin\Reports;

use App\Models\Payment;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsSummaryService
{
    public function getSummary(array $filters = [])
    {
        $dateFrom = $filters['dateFrom'] ?? Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = $filters['dateTo'] ?? Carbon::now()->format('Y-m-d');
        $programId = $filters['programId'] ?? null;

        return [
            'dailyPayments' => $this->getDailyPaymentsSummary($dateFrom, $dateTo, $programId),
            'consolidatedPayments' => $this->getConsolidatedPaymentsSummary($dateFrom, $dateTo, $programId),
            'paymentSchedule' => $this->getPaymentScheduleSummary($dateFrom, $dateTo, $programId),
            'partialAccount' => $this->getPartialAccountSummary($dateFrom, $dateTo, $programId),
            'general' => $this->getGeneralSummary($dateFrom, $dateTo, $programId)
        ];
    }

    private function getDailyPaymentsSummary($dateFrom, $dateTo, $programId)
    {
        $query = Payment::with(['order.program', 'order.participant'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        if ($programId) {
            $query->whereHas('order.program', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
        }

        $payments = $query->get();

        return [
            'totalPayments' => $payments->count(),
            'totalAmount' => $payments->sum('amount'),
            'averageAmount' => $payments->count() > 0 ? round($payments->sum('amount') / $payments->count(), 2) : 0,
            'paymentsToday' => $payments->where('created_at', '>=', Carbon::today())->count(),
            'amountToday' => $payments->where('created_at', '>=', Carbon::today())->sum('amount'),
            'topPaymentMethods' => $this->getTopPaymentMethods($payments),
            'dailyTrend' => $this->getDailyTrend($payments)
        ];
    }

    private function getConsolidatedPaymentsSummary($dateFrom, $dateTo, $programId)
    {
        $query = Payment::with(['order.program', 'order.participant'])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        if ($programId) {
            $query->whereHas('order.program', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
        }

        $payments = $query->get();

        // Agrupar por programa
        $byProgram = $payments->groupBy('order.program.id');

        return [
            'totalPrograms' => $byProgram->count(),
            'totalTransactions' => $payments->count(),
            'totalAmount' => $payments->sum('amount'),
            'averagePerProgram' => $byProgram->count() > 0 ? round($payments->sum('amount') / $byProgram->count(), 2) : 0,
            'topPrograms' => $this->getTopPrograms($payments),
            'paymentDistribution' => $this->getPaymentDistribution($payments)
        ];
    }

    private function getPaymentScheduleSummary($dateFrom, $dateTo, $programId)
    {
        $query = Order::with(['program', 'participant'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($programId) {
            $query->where('program_id', $programId);
        }

        $orders = $query->get();

        // Simular cuotas (en un sistema real esto vendría de una tabla de cuotas)
        $totalScheduled = $orders->count() * 3; // Asumiendo 3 cuotas por orden
        $totalPaid = $orders->where('status', 'completed')->count() * 3;
        $totalPending = $totalScheduled - $totalPaid;

        return [
            'totalScheduled' => $totalScheduled,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'completionRate' => $totalScheduled > 0 ? round(($totalPaid / $totalScheduled) * 100, 2) : 0,
            'overduePayments' => $this->getOverduePayments($orders),
            'upcomingPayments' => $this->getUpcomingPayments($orders)
        ];
    }

    private function getPartialAccountSummary($dateFrom, $dateTo, $programId)
    {
        $query = Participant::with(['programs'])
            ->whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($programId) {
            $query->whereHas('programs', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        $participants = $query->get();

        return [
            'totalParticipants' => $participants->count(),
            'activeParticipants' => $participants->where('status', 'active')->count(),
            'totalBalance' => $this->calculateTotalBalance($participants),
            'averageBalance' => $participants->count() > 0 ? round($this->calculateTotalBalance($participants) / $participants->count(), 2) : 0,
            'balanceDistribution' => $this->getBalanceDistribution($participants),
            'recentActivity' => $this->getRecentActivity($participants)
        ];
    }

    private function getGeneralSummary($dateFrom, $dateTo, $programId)
    {
        $payments = Payment::whereBetween('created_at', [$dateFrom, $dateTo])
            ->where('status', 'completed');

        $participants = Participant::whereBetween('created_at', [$dateFrom, $dateTo]);

        if ($programId) {
            $payments->whereHas('order.program', function ($q) use ($programId) {
                $q->where('id', $programId);
            });
            $participants->whereHas('programs', function ($q) use ($programId) {
                $q->where('program_id', $programId);
            });
        }

        $totalRevenue = $payments->sum('amount');
        $totalParticipants = $participants->count();

        return [
            'totalRevenue' => $totalRevenue,
            'totalParticipants' => $totalParticipants,
            'averageOrderValue' => $totalParticipants > 0 ? round($totalRevenue / $totalParticipants, 2) : 0,
            'conversionRate' => $this->calculateConversionRate($dateFrom, $dateTo, $programId),
            'growthRate' => $this->calculateGrowthRate($dateFrom, $dateTo, $programId)
        ];
    }

    private function getTopPaymentMethods($payments)
    {
        return $payments->groupBy('payment_gateway_id')
            ->map(function ($group) {
                $firstPayment = $group->first();
                $gatewayName = 'Desconocido';
                
                if ($firstPayment->paymentGateway) {
                    $gatewayName = $firstPayment->paymentGateway->name ?? $firstPayment->paymentGateway->code ?? 'Desconocido';
                }
                
                return [
                    'gateway' => $gatewayName,
                    'count' => $group->count(),
                    'amount' => $group->sum('amount')
                ];
            })
            ->sortByDesc('amount')
            ->take(3)
            ->values();
    }

    private function getDailyTrend($payments)
    {
        return $payments->groupBy(function ($payment) {
            return $payment->created_at->format('Y-m-d');
        })
        ->map(function ($group) {
            return [
                'date' => $group->first()->created_at->format('Y-m-d'),
                'count' => $group->count(),
                'amount' => $group->sum('amount')
            ];
        })
        ->sortBy('date')
        ->values();
    }

    private function getTopPrograms($payments)
    {
        return $payments->groupBy('order.program.id')
            ->map(function ($group) {
                return [
                    'name' => $group->first()->order->program->name ?? 'Sin nombre',
                    'count' => $group->count(),
                    'amount' => $group->sum('amount')
                ];
            })
            ->sortByDesc('amount')
            ->take(5)
            ->values();
    }

    private function getPaymentDistribution($payments)
    {
        $total = $payments->sum('amount');
        
        return $payments->groupBy('payment_gateway_id')
            ->map(function ($group) use ($total) {
                $firstPayment = $group->first();
                $gatewayName = 'Desconocido';
                
                if ($firstPayment->paymentGateway) {
                    $gatewayName = $firstPayment->paymentGateway->name ?? $firstPayment->paymentGateway->code ?? 'Desconocido';
                }
                
                return [
                    'gateway' => $gatewayName,
                    'percentage' => $total > 0 ? round(($group->sum('amount') / $total) * 100, 2) : 0
                ];
            })
            ->values();
    }

    private function getOverduePayments($orders)
    {
        // Simulación de pagos vencidos
        return $orders->where('status', 'pending')->count();
    }

    private function getUpcomingPayments($orders)
    {
        // Simulación de próximos pagos
        return $orders->where('status', 'pending')->count();
    }

    private function calculateTotalBalance($participants)
    {
        // Simulación del balance total
        return $participants->count() * 1000; // Valor simulado
    }

    private function getBalanceDistribution($participants)
    {
        // Simulación de distribución de balances
        return [
            ['range' => '0-500', 'count' => $participants->count() * 0.3],
            ['range' => '501-1000', 'count' => $participants->count() * 0.4],
            ['range' => '1001+', 'count' => $participants->count() * 0.3]
        ];
    }

    private function getRecentActivity($participants)
    {
        return $participants->where('created_at', '>=', Carbon::now()->subDays(7))->count();
    }

    private function calculateConversionRate($dateFrom, $dateTo, $programId)
    {
        // Simulación de tasa de conversión
        return 75.5; // Porcentaje simulado
    }

    private function calculateGrowthRate($dateFrom, $dateTo, $programId)
    {
        // Simulación de tasa de crecimiento
        return 12.3; // Porcentaje simulado
    }
}
