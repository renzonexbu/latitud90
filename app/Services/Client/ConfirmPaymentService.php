<?php

namespace App\Services\Client;

use App\Models\Program;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmPaymentService
{
    public function getConfirmationDetails($programId, $participantId = null)
    {
        $program = Program::find($programId);
        if (!$program) {
            return null;
        }

        // Obtener datos del localStorage (simulado)
        $formData = $this->getFormDataFromSession();
        $paymentData = $this->getPaymentDataFromSession();

        return [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'destination' => $program->destination,
                'trip_price' => $program->trip_price,
                'departure_date' => $program->departure_date,
                'final_payment_date' => $program->final_payment_date,
                'max_installments' => $program->max_installments,
            ],
            'participant' => $participantId ? [
                'id' => $participantId,
            ] : null,
            'form_data' => $formData,
            'payment_data' => $paymentData,
            'confirmation_number' => 'CONF-' . time() . '-' . rand(1000, 9999),
            'status' => 'confirmed'
        ];
    }

    private function getFormDataFromSession()
    {
        // En un caso real, esto vendría de la base de datos
        return [
            'name' => 'Usuario Ejemplo',
            'document_number' => '12345678-9',
            'email' => 'usuario@ejemplo.com',
            'phone' => '912345678',
            'code_phone' => '+56',
            'country' => 'Chile',
            'region' => 'metropolitana',
            'city' => 'santiago',
            'termsAccepted' => true,
            'marketingAccepted' => true
        ];
    }

    private function getPaymentDataFromSession()
    {
        // En un caso real, esto vendría de la base de datos
        return [
            'paymentType' => 'total',
            'paymentMethod' => 'debit',
            'installments' => 1
        ];
    }
}
