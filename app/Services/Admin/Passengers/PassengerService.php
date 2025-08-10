<?php

namespace App\Services\Admin\Passengers;

use App\Models\Participant as Passenger;
use App\Models\Program;

class PassengerService
{
    public function create(array $data): Passenger
    {
        $program = Program::findOrFail($data['program_id']);
        $data['individual_price'] = $program->price_per_passenger;
        return Passenger::create($data);
    }

    public function update(array $data, Passenger $passenger): Passenger
    {
        $passenger->update($data);
        return $passenger;
    }

    public function updatePrice(array $data, Passenger $passenger): void
    {
        $passenger->update([
            'price_adjustments' => $passenger->price_adjustments + $data['price_adjustment'],
            'adjustment_reason' => $data['adjustment_reason'],
        ]);
    }

    public function generatePaymentLink(array $data, Passenger $passenger): string
    {
        $paymentLink = $passenger->paymentLinks()->create([
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'description' => $data['description'],
            'status' => 'active',
            'expires_at' => now()->addHours($data['expires_hours']),
        ]);
        return $paymentLink->url;
    }
}


