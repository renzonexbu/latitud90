<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    /**
     * Método temporal para preview del email de no payment
     */
    public function previewNoPaymentEmail()
    {
        $testData = [
            'participant_name' => 'Juan Carlos Pérez González',
            'incorporation_date' => '15/12/2024',
            'program_amount' => '1.500.000',
            'program_name' => 'Programa de Liderazgo 2025',
            'emergency_contact_name' => 'María González'
        ];
        
        return view('Mails.no_payment', $testData);
    }
    
    /**
     * Método temporal para preview del email de éxito de pago
     */
    public function previewSuccessPaymentEmail()
    {
        $testData = [
            'company_name' => 'Latitud 90',
            'customer_name' => 'Juan Carlos Pérez González',
            'program_name' => 'Programa de Liderazgo 2025',
            'program_description' => 'Programa intensivo de desarrollo de habilidades de liderazgo',
            'order_number' => 'ORD-2024-001234',
            'payment_amount' => '1.500.000',
            'payment_currency' => 'CLP',
            'payment_method' => 'Tarjeta de Crédito',
            'payment_date' => '15/12/2024',
            'transaction_id' => 'TXN-789456123',
            'total_installments' => 3,
            'installment_number' => 1,
            'company_email' => 'contacto@latitud90.cl',
            'company_phone' => '+56 9 1234 5678'
        ];
        
        return view('Mails.success_payment', $testData);
    }
}
