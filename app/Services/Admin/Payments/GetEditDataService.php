<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Models\Program;
use App\Models\PaymentGateway;
use App\Models\PaymentOption;

class GetEditDataService
{
    /**
     * Obtener datos necesarios para editar un pago
     *
     * @param Payment $payment
     * @return array
     */
    public function execute(Payment $payment): array
    {
        // Cargar relaciones del pago
        $payment->load([
            'order', 
            'order.participant', 
            'order.program.course.institution', 
            'orderDetail.country', 
            'orderDetail.region', 
            'orderDetail.city', 
            'paymentGateway', 
            'paymentOption'
        ]);
        
        // Obtener programas con participantes
        $programs = Program::with(['course.participants', 'course.institution'])
            ->where('active', true)
            ->orderBy('name')
            ->get();
            
        $paymentGateways = PaymentGateway::where('active', true)->get();
        $paymentOptions = PaymentOption::where('active', true)->get();

        return [
            'payment' => $payment,
            'programs' => $programs,
            'paymentGateways' => $paymentGateways,
            'paymentOptions' => $paymentOptions
        ];
    }
}
