<?php

namespace App\Services\Client;

use App\Models\Program;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmPaymentService
{
    public function getConfirmationDetails($programId, $participantId = null, $rut = null)
    {
        $program = Program::find($programId);
        if (!$program) {
            return null;
        }

        // Buscar datos reales del participante por RUT
        $formData = $this->getFormDataFromRut($rut);
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
                // Propiedades necesarias para PaymentPanel
                'enable_total_payment' => $program->enable_total_payment,
                'enable_lat90_payment' => $program->enable_lat90_payment,
                'total_payment_method_id' => $program->total_payment_method_id,
                'lat90_payment_method_id' => $program->lat90_payment_method_id,
                'lat90_max_installments' => $program->lat90_max_installments,
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

    private function getFormDataFromRut($rut)
    {
        if (!$rut) {
            return $this->getFormDataFromSession();
        }

        // Buscar participante por RUT en la tabla participants
        $participant = Participant::where('document_number', $rut)->first();
        
        if ($participant) {
            $formData = [
                'name' => $participant->first_name . ' ' . $participant->last_name,
                'document_number' => $participant->document_number,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'code_phone' => $participant->code_phone ?? '+56',
                'country' => $participant->country ?? 'Chile',
                'region' => '',
                'city' => '',
                'termsAccepted' => true,
                'marketingAccepted' => true
            ];
            
            return $formData;
        }

        // Si no se encuentra, devolver datos por defecto
        return $this->getFormDataFromSession();
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
