<?php

namespace App\Services\Client;

use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use Illuminate\Support\Facades\Log;

class GetSuccessDataService
{
    public function __construct(
        private PaymentConfirmationService $paymentConfirmationService
    ) {}

    /**
     * Obtener datos para la vista de éxito de pago
     *
     * @param int $orderDetailId
     * @return array
     * @throws \Exception
     */
    public function execute(int $orderDetailId): array
    {
        try {
            $paymentData = $this->paymentConfirmationService->getPaymentData($orderDetailId);
            
            // Preservar RUT en sesión
            if ($paymentData['order_detail']->document_number) {
                session(['current_rut' => preg_replace('/[.-]/', '', $paymentData['order_detail']->document_number)]);
            }

            return [
                'paymentData' => $paymentData,
                'rut' => session('current_rut'),
            ];
        } catch (\Exception $e) {
            Log::error('GetSuccessDataService: Error showing success', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
            ]);
            
            throw $e;
        }
    }
}
