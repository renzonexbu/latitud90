<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;

class ReportsService
{
    public function daily(array $filters)
    {
        $query = Payment::with(['passenger.program.commercialExecutive'])
            ->whereDate('created_at', $filters['date'])
            ->where('status', 'approved');

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

        return [$payments, $summary];
    }

    public function consolidated(array $filters)
    {
        $query = Payment::with(['passenger.program'])
            ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']])
            ->where('status', 'approved');

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->get();
    }
}


