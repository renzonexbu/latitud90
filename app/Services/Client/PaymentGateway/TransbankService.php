<?php

namespace App\Services\Client\PaymentGateway;

use App\Models\Payment;
use App\Models\Passenger;
use Illuminate\Support\Facades\Log;
use Transbank\Webpay\WebpayPlus\MallTransaction;
use Transbank\Webpay\Options;
use Transbank\Webpay\WebpayPlus\Exceptions\MallTransactionCreateException;
use Transbank\Webpay\WebpayPlus\Exceptions\MallTransactionCommitException;

class TransbankService
{
    private string $apiKey;
    private string $parentCommerceCode;
    private string $childCommerceCode;
    private string $environment;

    public function __construct()
    {
        $this->apiKey = (string) config('services.transbank.api_key');
        // Códigos Mall Webpay Plus (por defecto, integración oficial)
        $this->parentCommerceCode = (string) config('services.transbank.commerce_code', '597055555535');
        $this->childCommerceCode = (string) config('services.transbank.child_commerce_code', '597055555536');
        $this->environment = (string) config('services.transbank.environment', 'integration');

        // Normalizar a códigos Mall por defecto en integración si se detecta código estándar
        if ($this->environment === 'integration') {
            if ($this->parentCommerceCode === '597055555532') { // estándar
                $this->parentCommerceCode = '597055555535'; // mall parent
            }
            if (empty($this->childCommerceCode) || $this->childCommerceCode === '597055555540') { // estándar diferido
                $this->childCommerceCode = '597055555536'; // mall child
            }
        }
    }

    public function createTransaction($orderId, $amount, $returnUrl, $notificationUrl = null, $paymentType = null, $installments = null)
    {
        try {
            $sessionId = session()->getId();

            // Detalle Mall: por ahora sin cuotas (tratar todos como débito)
            $details = [
                [
                    'amount' => (int) $amount,
                    'commerce_code' => $this->childCommerceCode,
                    'buy_order' => (string) $orderId . '-1',
                ],
            ];

            // Construir instancia del SDK con Options (commerce code + api key)
            $options = new Options($this->apiKey, $this->parentCommerceCode, $this->environment);
            $mall = new MallTransaction($options);
            $response = $mall->create((string) $orderId, (string) $sessionId, (string) $returnUrl, $details);

            return [
                'success' => true,
                'token' => $response->getToken(),
                'url' => $response->getUrl(),
            ];
        } catch (MallTransactionCreateException $e) {
            return [
                'success' => false,
                'error' => method_exists($e, 'getTransbankErrorMessage') && $e->getTransbankErrorMessage() ? $e->getTransbankErrorMessage() : $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }



    public function confirmTransaction($token)
    {
        try {
            $options = new Options($this->apiKey, $this->parentCommerceCode, $this->environment);
            $mall = new MallTransaction($options);
            $commit = $mall->commit((string) $token);

            $details = $commit->getDetails();
            $firstDetail = $details[0] ?? null;
            $isSuccessful = false;
            foreach ($details as $detail) {
                if ($detail->getResponseCode() === 0) {
                    $isSuccessful = true;
                    break;
                }
            }
            $response = [
                'success' => $isSuccessful,
                'response_code' => $isSuccessful ? 0 : -1,
                'authorization_code' => $firstDetail ? $firstDetail->getAuthorizationCode() : null,
                'amount' => $firstDetail ? $firstDetail->getAmount() : null,
                'buy_order' => $commit->getBuyOrder(),
                'session_id' => $commit->getSessionId(),
                'card_detail' => $commit->getCardDetail(),
                'accounting_date' => $commit->getAccountingDate(),
                'transaction_date' => $commit->getTransactionDate(),
                'vci' => $commit->getVci(),
                'full_response' => [
                    'buy_order' => $commit->getBuyOrder(),
                    'session_id' => $commit->getSessionId(),
                    'details' => array_map(function ($d) {
                        return [
                            'amount' => $d->getAmount(),
                            'status' => $d->getStatus(),
                            'authorization_code' => $d->getAuthorizationCode(),
                            'payment_type_code' => $d->getPaymentTypeCode(),
                            'response_code' => $d->getResponseCode(),
                            'installments_number' => $d->getInstallmentsNumber(),
                            'commerce_code' => $d->getCommerceCode(),
                            'buy_order' => $d->getBuyOrder(),
                        ];
                    }, $details),
                    'card_detail' => $commit->getCardDetail(),
                    'accounting_date' => $commit->getAccountingDate(),
                    'transaction_date' => $commit->getTransactionDate(),
                    'vci' => $commit->getVci(),
                ],
            ];
            Log::info('TransbankService: confirmTransaction response', [
                'response' => $response
            ]);
            return $response;
        } catch (MallTransactionCommitException $e) {
            return [
                'success' => false,
                'error' => method_exists($e, 'getTransbankErrorMessage') && $e->getTransbankErrorMessage() ? $e->getTransbankErrorMessage() : $e->getMessage(),
            ];
        } catch (\Exception $e) {
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
