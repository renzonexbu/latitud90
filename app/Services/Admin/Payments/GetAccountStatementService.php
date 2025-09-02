<?php

namespace App\Services\Admin\Payments;

use App\Models\Participant as Passenger;

class GetAccountStatementService
{
    /**
     * Obtener estado de cuenta de un pasajero
     *
     * @param Passenger $passenger
     * @return array
     */
    public function execute(Passenger $passenger): array
    {
        $passenger->load(['program', 'payments', 'contracts']);
        
        $statement = [
            'passenger' => $passenger,
            'total_program_price' => $passenger->individual_price + $passenger->price_adjustments,
            'total_paid' => $passenger->total_paid,
            'pending_amount' => $passenger->pending_amount,
            'payments_history' => $passenger->payments()->orderBy('created_at', 'desc')->get(),
            'payment_progress' => $passenger->total_paid > 0 ? 
                round(($passenger->total_paid / ($passenger->individual_price + $passenger->price_adjustments)) * 100, 2) : 0
        ];

        return [
            'statement' => $statement
        ];
    }
}
