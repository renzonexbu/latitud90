<?php

namespace App\Services\Admin\Courses;

use App\Models\Course;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class CoursePaymentService
{
    public function getPaymentSummary(Course $course): array
    {
        $payments = Payment::query()
            ->select([
                'payments.status',
                DB::raw('COUNT(DISTINCT payments.id) as count'),
                DB::raw('SUM(payments.amount) as total')
            ])
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.program_id', $course->program_id)
            ->groupBy('payments.status')
            ->get()
            ->keyBy('status');

        return [
            'total' => $payments->sum('total'),
            'approved' => $payments->get('approved', (object)['total' => 0])->total,
            'pending' => $payments->get('pending', (object)['total' => 0])->total,
            'rejected' => $payments->get('rejected', (object)['total' => 0])->total,
        ];
    }

    public function getPaymentMethodsSummary(Course $course): array
    {
        return Payment::query()
            ->select([
                'payment_options.name as method',
                DB::raw('COUNT(DISTINCT payments.id) as count'),
                DB::raw('SUM(payments.amount) as total')
            ])
            ->leftJoin('payment_options', 'payments.payment_option_id', '=', 'payment_options.id')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.program_id', $course->program_id)
            ->whereIn('payments.status', ['approved', 'completed'])
            ->groupBy('payment_options.name')
            ->get()
            ->mapWithKeys(fn($item) => [
                $item->method => [
                    'count' => $item->count,
                    'total' => (float) $item->total
                ]
            ])
            ->toArray();
    }

    public function getRecentPayments(Course $course, int $limit = 5)
    {
        return Payment::with(['paymentOption', 'order.participant'])
            ->whereHas('order', function($query) use ($course) {
                $query->where('program_id', $course->program_id);
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
