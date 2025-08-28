<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Traits\AdminLogging;

class ReportsService
{
    use AdminLogging;
    public function daily(array $filters)
    {
        $query = Payment::with(['passenger.program.commercialExecutive'])
            ->whereDate('created_at', $filters['date'])
            ->whereIn('status', ['approved', 'completed', 'paid', 'success']);

        if (!empty($filters['program_id'])) {
            $query->whereHas('passenger', function($q) use ($filters) {
                $q->where('program_id', $filters['program_id']);
            });
        }

        if (!empty($filters['executive_id'])) {
            $query->whereHas('passenger.program', function($q) use ($filters) {
                $q->where('commercial_executive_id', $filters['executive_id']);
            });
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $payments = $query->get();

        $summary = [
            'total_amount' => $payments->sum('amount'),
            'total_transactions' => $payments->count(),
            'by_method' => $payments->groupBy('payment_method')->map->sum('amount'),
            'by_program' => $payments->groupBy('passenger.program.program_number')->map->sum('amount'),
        ];

        // Log the report generation
        $this->logExport(
            'payments',
            "Reporte diario de pagos generado: {$filters['date']}",
            [
                'report_type' => 'daily',
                'date' => $filters['date'],
                'filters' => $filters,
                'total_amount' => $summary['total_amount'],
                'total_transactions' => $summary['total_transactions'],
                'program_id' => $filters['program_id'] ?? null,
                'executive_id' => $filters['executive_id'] ?? null,
                'payment_method' => $filters['payment_method'] ?? null,
            ]
        );

        return [$payments, $summary];
    }

    public function consolidated(array $filters)
    {
        $query = Payment::with(['passenger.program'])
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->whereIn('status', ['approved', 'completed', 'paid', 'success']);

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        $payments = $query->get();

        // Log the consolidated report generation
        $this->logExport(
            'payments',
            "Reporte consolidado de pagos generado: {$filters['start_date']} - {$filters['end_date']}",
            [
                'report_type' => 'consolidated',
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
                'filters' => $filters,
                'total_transactions' => $payments->count(),
                'total_amount' => $payments->sum('amount'),
                'payment_method' => $filters['payment_method'] ?? null,
            ]
        );

        return $payments;
    }
}


