<?php

namespace App\Services\Client\PaymentGateway;

use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\Options;
use App\Models\Payment;
use App\Models\Passenger;
use Illuminate\Support\Facades\Log;

class TransbankService
{
    private $transaction;

    public function __construct()
    {
        // Configuración para ambiente de desarrollo (integración)
        $this->transaction = new Transaction();
        $this->transaction->configureForIntegration(
            config('services.transbank.commerce_code'),
            config('services.transbank.api_key')
        );
    }

    public function createTransaction($orderId, $amount, $returnUrl, $notificationUrl = null, $paymentType = null, $installments = null)
    {
        try {
            $sessionId = session()->getId();
            
            // Determinar si forzar a 1 cuota para pagos mensuales
            $forceSingleInstallment = ($paymentType === 'monthly' && $installments);
            
            if ($forceSingleInstallment) {
                Log::info('Monthly credit card payment detected - using REST API to force 1 installment', [
                    'order_id' => $orderId,
                    'payment_type' => $paymentType,
                    'requested_installments' => $installments
                ]);
                
                // Usar API REST para forzar 1 cuota
                return $this->createTransactionWithInstallmentsControl($orderId, $sessionId, $amount, $returnUrl, 1);
            }
            
            // Usar SDK estándar para otros casos
            $response = $this->transaction->create(
                $orderId,
                $sessionId,
                $amount,
                $returnUrl
            );

            Log::info('Transbank transaction created', [
                'order_id' => $orderId,
                'amount' => $amount,
                'token' => $response->getToken(),
                'url' => $response->getUrl(),
                'notification_url' => $notificationUrl,
                'payment_type' => $paymentType,
                'installments' => $installments
            ]);

            return [
                'success' => true,
                'token' => $response->getToken(),
                'url' => $response->getUrl()
            ];
        } catch (\Exception $e) {
            Log::error('Error creating Transbank transaction', [
                'error' => $e->getMessage(),
                'order_id' => $orderId,
                'amount' => $amount,
                'payment_type' => $paymentType
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear transacción usando API REST para controlar cuotas
     */
    private function createTransactionWithInstallmentsControl($orderId, $sessionId, $amount, $returnUrl, $installmentsNumber = 1)
    {
        try {
            $apiKey = config('services.transbank.api_key');
            $commerceCode = config('services.transbank.commerce_code');
            $environment = config('services.transbank.environment', 'integration');
            
            // URL base según el ambiente
            $baseUrl = $environment === 'production' 
                ? config('services.transbank.production_url', 'https://webpay3g.transbank.cl')
                : config('services.transbank.base_url', 'https://webpay3gint.transbank.cl');
            
            $url = $baseUrl . '/rswebpaytransaction/api/webpay/v1.2/transactions';
            
            $payload = [
                'buy_order' => $orderId,
                'session_id' => $sessionId,
                'amount' => $amount,
                'return_url' => $returnUrl,
                'installments_number' => $installmentsNumber // Forzar número de cuotas
            ];
            
            $headers = [
                'Content-Type: application/json',
                'Tbk-Api-Key-Id: ' . $commerceCode,
                'Tbk-Api-Key-Secret: ' . $apiKey
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                Log::info('Transbank REST API transaction created', [
                    'order_id' => $orderId,
                    'amount' => $amount,
                    'token' => $data['token'] ?? 'unknown',
                    'url' => $data['url'] ?? 'unknown',
                    'installments_number' => $installmentsNumber
                ]);
                
                return [
                    'success' => true,
                    'token' => $data['token'] ?? '',
                    'url' => $data['url'] ?? ''
                ];
            } else {
                Log::error('Transbank REST API error', [
                    'http_code' => $httpCode,
                    'response' => $response,
                    'order_id' => $orderId
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Error en API REST de Transbank: ' . $httpCode
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error creating Transbank REST transaction', [
                'error' => $e->getMessage(),
                'order_id' => $orderId
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function confirmTransaction($token)
    {
        try {
            $response = $this->transaction->commit($token);

            Log::info('Transbank transaction confirmed', [
                'token' => $token,
                'response_code' => $response->getResponseCode(),
                'authorization_code' => $response->getAuthorizationCode(),
                'amount' => $response->getAmount()
            ]);

            return [
                'success' => $response->getResponseCode() === 0,
                'response_code' => $response->getResponseCode(),
                'authorization_code' => $response->getAuthorizationCode(),
                'amount' => $response->getAmount(),
                'buy_order' => $response->getBuyOrder(),
                'session_id' => $response->getSessionId(),
                'card_detail' => $response->getCardDetail(),
                'accounting_date' => $response->getAccountingDate(),
                'transaction_date' => $response->getTransactionDate(),
                'vci' => $response->getVci(),
                'full_response' => $response
            ];
        } catch (\Exception $e) {
            Log::error('Error confirming Transbank transaction', [
                'error' => $e->getMessage(),
                'token' => $token
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function processPayment($paymentId, $token)
    {
        $payment = Payment::findOrFail($paymentId);
        $confirmation = $this->confirmTransaction($token);

        if ($confirmation['success']) {
            $payment->update([
                'status' => 'approved',
                'transaction_id' => $token,
                'authorization_number' => $confirmation['authorization_code'],
                'gateway_response' => $confirmation['full_response']
            ]);

            // Crear contrato si es necesario
            $this->createContractIfNeeded($payment);

            return [
                'success' => true,
                'payment' => $payment,
                'confirmation' => $confirmation
            ];
        } else {
            $payment->update([
                'status' => 'rejected',
                'gateway_response' => $confirmation
            ]);

            return [
                'success' => false,
                'payment' => $payment,
                'error' => $confirmation['error'] ?? 'Transaction failed'
            ];
        }
    }

    private function createContractIfNeeded($payment)
    {
        $passenger = $payment->passenger;
        $program = $passenger->program;

        // Si es un servicio de reserva y es el primer pago, crear contrato
        if ($program->service_type === 'reservation' && !$passenger->contracts()->exists()) {
            $contract = $passenger->contracts()->create([
                'contract_number' => 'C-' . $program->program_number . '-' . $passenger->id,
                'contract_type' => 'reservation',
                'total_amount' => $passenger->individual_price + $passenger->price_adjustments,
                'paid_amount' => $payment->amount,
                'pending_amount' => ($passenger->individual_price + $passenger->price_adjustments) - $payment->amount,
                'status' => 'active'
            ]);

            $contract->addVoucher($payment);
        }
    }
}
