<?php

namespace App\Services\Client\Data;

use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use App\Traits\SystemLogging;
use Illuminate\Http\Request;

class GetFailureDataService
{
    use SystemLogging;
    public function __construct(
        private PaymentConfirmationService $paymentConfirmationService
    ) {}

    /**
     * Obtener datos para la vista de fallo de pago
     *
     * @param Request $request
     * @param int $orderDetailId
     * @return array
     */
    public function execute(Request $request, int $orderDetailId): array
    {
        try {
            $paymentData = $this->paymentConfirmationService->getPaymentData($orderDetailId);
            $errorMessage = $request->session()->get('error', 'El pago no pudo ser procesado correctamente.');
            
            // Preservar RUT en sesión
            if ($paymentData['order_detail']->document_number) {
                session(['current_rut' => preg_replace('/[.-]/', '', $paymentData['order_detail']->document_number)]);
            }

            return [
                'paymentData' => $paymentData,
                'errorMessage' => $errorMessage,
                'rut' => session('current_rut'),
            ];
        } catch (\Exception $e) {
            $this->logError('GetFailureDataService: Error showing failure', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
            ], $e);
            
            return [
                'paymentData' => null,
                'errorMessage' => 'Error al procesar el resultado del pago.',
                'rut' => session('current_rut'),
            ];
        }
    }
}
