<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;

class GetInstallmentScheduleService
{
    /**
     * Obtener cronograma de cuotas pendientes
     *
     * @return array
     */
    public function execute(): array
    {
        $upcomingPayments = Payment::with(['order.participant', 'order.program'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return [
            'upcomingPayments' => $upcomingPayments
        ];
    }
}
