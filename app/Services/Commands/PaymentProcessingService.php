<?php

namespace App\Services\Commands;

use App\Models\Payment;
use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    private PaymentConfirmationService $paymentConfirmationService;

    public function __construct(PaymentConfirmationService $paymentConfirmationService)
    {
        $this->paymentConfirmationService = $paymentConfirmationService;
    }

    /**
     * Procesar pagos pendientes de confirmación
     */
    public function processPendingPayments(): array
    {
        $results = [
            'total_pending' => 0,
            'processed' => 0,
            'errors' => 0,
            'details' => []
        ];
        
        try {
            // Obtener pagos pendientes con sus relaciones necesarias
            $pendingPayments = Payment::where('status', 'pending')
                ->with(['orderDetail.order', 'paymentGateway'])
                ->get();
                
            $results['total_pending'] = $pendingPayments->count();
            
            if ($pendingPayments->isEmpty()) {
                Log::info('No hay pagos pendientes para procesar');
                return $results;
            }
            
            Log::info("Procesando {$results['total_pending']} pagos pendientes");
            
            foreach ($pendingPayments as $payment) {
                try {
                    $this->processPayment($payment);
                    $results['processed']++;
                    
                    $results['details'][] = [
                        'payment_id' => $payment->id,
                        'order_detail_id' => $payment->order_detail_id,
                        'status' => 'processed',
                        'message' => 'Pago procesado exitosamente'
                    ];
                    
                } catch (\Exception $e) {
                    $results['errors']++;
                    
                    $results['details'][] = [
                        'payment_id' => $payment->id,
                        'order_detail_id' => $payment->order_detail_id,
                        'status' => 'error',
                        'message' => $e->getMessage()
                    ];
                    
                    Log::error("Error procesando pago ID: {$payment->id}", [
                        'error' => $e->getMessage(),
                        'payment_data' => $payment->toArray()
                    ]);
                }
            }
            
            Log::info('Procesamiento de pagos pendientes completado', $results);
            
        } catch (\Exception $e) {
            Log::error('Error general procesando pagos pendientes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
        
        return $results;
    }
    
    /**
     * Procesar un pago individual
     */
    private function processPayment(Payment $payment): void
    {
        $gatewayCode = $payment->paymentGateway->code ?? 'unknown';
        
        Log::info("Procesando pago ID: {$payment->id} con gateway: {$gatewayCode}");
        
        // Construir los datos del gateway según el tipo
        $gatewayData = $this->buildGatewayData($payment);
        
        if (empty($gatewayData)) {
            Log::warning("No se pudieron obtener datos del gateway para pago ID: {$payment->id}", [
                'gateway_code' => $gatewayCode,
                'payment_data' => $payment->toArray()
            ]);
            return;
        }
        
        // Usar el PaymentConfirmationService para confirmar el pago
        $result = $this->paymentConfirmationService->confirmPayment(
            $payment->order_detail_id,
            $gatewayCode,
            $gatewayData
        );
        
        Log::info("Resultado de confirmación para pago ID: {$payment->id}", [
            'result' => $result,
            'gateway_code' => $gatewayCode
        ]);
    }
    
    /**
     * Construir datos del gateway según el tipo de pago
     */
    private function buildGatewayData(Payment $payment): array
    {
        $gatewayCode = $payment->paymentGateway->code ?? 'unknown';
        $gatewayData = [];
        
        switch ($gatewayCode) {
            case 'transbank':
            case 'virtualpos':
                // Para Transbank/VirtualPOS necesitamos el token o payment_id
                if ($payment->token) {
                    $gatewayData['token_ws'] = $payment->token;
                } elseif ($payment->external_payment_id) {
                    $gatewayData['payment_id'] = $payment->external_payment_id;
                }
                break;
                
            case 'khipu':
                // Para Khipu necesitamos el external_payment_id
                if ($payment->external_payment_id) {
                    $gatewayData['payment_id'] = $payment->external_payment_id;
                }
                break;
                
            default:
                Log::warning("Gateway no soportado para construcción de datos: {$gatewayCode}");
                break;
        }
        
        return $gatewayData;
    }
}
