<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\PaymentGateway\PaymentConfirmationService;
use App\Services\Client\Data\GetSpinnerDataService;
use App\Services\Client\Payment\ProcessPaymentConfirmationService;
use App\Services\Client\Data\GetSuccessDataService;
use App\Services\Client\Data\GetFailureDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentConfirmationController extends Controller
{
    public function __construct(
        private PaymentConfirmationService $paymentConfirmationService,
        private GetSpinnerDataService $getSpinnerDataService,
        private ProcessPaymentConfirmationService $processPaymentConfirmationService,
        private GetSuccessDataService $getSuccessDataService,
        private GetFailureDataService $getFailureDataService
    ) {}

    /**
     * Vista de spinner unificada para todas las pasarelas
     */
    public function showSpinner(Request $request, $orderDetailId)
    {
        // LÓGICA DINÁMICA: Determinar qué gateway usar según configuración
        $useVirtualPos = config('lat90.payment.use_virtualpos', true);
        
        // LOG PRINCIPAL PARA VERIFICAR QUÉ GATEWAY SE ESTÁ USANDO EN CONFIRMACIÓN
        Log::info('🔄 PAYMENT CONFIRMATION - Config value: ' . ($useVirtualPos ? 'true' : 'false'), [
            'gateway_expected' => $useVirtualPos ? 'VIRTUALPOS (PRODUCCIÓN)' : 'TRANSBANK (PRUEBAS)',
            'order_detail_id' => $orderDetailId,
            'config_file' => 'config/lat90.php',
            'config_key' => 'lat90.payment.use_virtualpos'
        ]);
        
        if ($useVirtualPos) {
            // LÓGICA PARA VIRTUALPOS (PRODUCCIÓN)
            if ($request->has('uuid') && $request->input('gateway') === 'virtualpos') {
                Log::info('VirtualPOS callback received in showSpinner (PRODUCCIÓN)', [
                    'order_detail_id' => $orderDetailId,
                    'data' => $request->all()
                ]);
                
                $paymentId = $request->input('uuid');
                
                // Buscar el pago
                $payment = \App\Models\Payment::where('token', $paymentId)
                    ->orWhere('external_payment_id', $paymentId)
                    ->first();
                
                if ($payment) {
                    // Consultar el estado del pago en VirtualPOS
                    $virtualPosService = app(\App\Services\Client\PaymentGateway\VirtualPosService::class);
                    $confirmResult = $virtualPosService->confirmTransaction($paymentId);
                    
                    if (isset($confirmResult['full_response']['payment']['order']['status'])) {
                        $status = $confirmResult['full_response']['payment']['order']['status'];
                        
                        // Si el pago fue rechazado/cancelado, redirigir a fallo
                        if (in_array($status, ['rechazado', 'cancelado', 'cancelled', 'rejected', 'failed'])) {
                            $payment->update([
                                'status' => 'failed',
                                'gateway_response' => $confirmResult,
                            ]);

                            $orderDetail = $payment->orderDetail;
                            if ($orderDetail) {
                                $orderDetail->update([
                                    'status' => 'failed',
                                    'is_paid' => false,
                                ]);
                            }
                            
                            return redirect()->route('payment.failure', ['orderDetailId' => $orderDetailId])
                                ->with('status', 'canceled');
                        }
                        // Si está aprobado, usar el servicio centralizado para evitar duplicación
                        elseif (in_array($status, ['aprobado', 'approved', 'paid', 'success'])) {
                            // Usar el servicio centralizado para procesar el pago exitoso
                            // Esto evitará duplicación de emails y asegurará consistencia
                            $this->paymentConfirmationService->confirmPayment(
                                $orderDetailId,
                                'virtualpos',
                                $confirmResult
                            );
                            
                            return redirect()->route('payment.success', ['orderDetailId' => $orderDetailId]);
                        }
                    }
                }
            }
        } else {
            // LÓGICA PARA TRANSBANK (PRUEBAS)
            Log::info('💳 TRANSBANK CONFIRMATION - Processing Transbank callback (PRUEBAS)', [
                'order_detail_id' => $orderDetailId,
                'gateway' => 'TRANSBANK'
            ]);
            
            if ($request->has('token_ws') && $request->input('gateway') === 'transbank') {
                Log::info('Transbank callback received in showSpinner (PRUEBAS)', [
                    'order_detail_id' => $orderDetailId,
                    'data' => $request->all()
                ]);
                
                $token = $request->input('token_ws');
                
                // Buscar el pago
                $payment = \App\Models\Payment::where('token', $token)
                    ->orWhere('external_payment_id', $token)
                    ->first();
                
                if ($payment) {
                    Log::info('💳 TRANSBANK PAYMENT FOUND - Processing confirmation', [
                        'payment_id' => $payment->id,
                        'payment_status' => $payment->status,
                        'token' => $token,
                        'order_detail_id' => $orderDetailId
                    ]);
                    
                    // Consultar el estado del pago en Transbank
                    $transbankService = app(\App\Services\Client\PaymentGateway\TransbankService::class);
                    $confirmResult = $transbankService->confirmTransaction($token);
                    
                    Log::info('💳 TRANSBANK CONFIRMATION RESULT - EXACT RESPONSE', [
                        'confirm_result' => $confirmResult,
                        'success' => $confirmResult['success'] ?? false,
                        'status' => $confirmResult['status'] ?? 'NO_STATUS',
                        'message' => $confirmResult['message'] ?? 'NO_MESSAGE',
                        'order_detail_id' => $orderDetailId,
                        'full_response' => json_encode($confirmResult)
                    ]);
                    
                    if ($confirmResult['success']) {
                        Log::info('💳 TRANSBANK PAYMENT SUCCESS - Updating database directly', [
                            'order_detail_id' => $orderDetailId,
                            'gateway' => 'transbank',
                            'confirm_result' => $confirmResult
                        ]);
                        
                        // ACTUALIZACIÓN DIRECTA DE LA BASE DE DATOS (como en Khipu)
                        $fullResponse = $confirmResult['full_response'] ?? [];
                        $details = $fullResponse['details'][0] ?? [];
                        
                        Log::info('💳 TRANSBANK EXTRACTING DETAILS', [
                            'full_response' => $fullResponse,
                            'details' => $details,
                            'payment_type_code' => $details['payment_type_code'] ?? 'NOT_FOUND',
                            'installments_number' => $details['installments_number'] ?? 'NOT_FOUND'
                        ]);
                        
                        $updateData = [
                            'status' => 'completed',
                            'authorization_code' => $confirmResult['authorization_code'] ?? null,
                            'card_type' => $details['payment_type_code'] ?? null,
                            'installments_number' => $details['installments_number'] ?? null,
                            'gateway_response' => $confirmResult,
                            'transaction_date' => now()->setTimezone('America/Santiago'),
                        ];
                        
                        Log::info('💳 TRANSBANK BEFORE UPDATE - Data to be updated', [
                            'payment_id' => $payment->id,
                            'update_data' => $updateData,
                            'installments_number_value' => $details['installments_number'] ?? 'NULL',
                            'installments_number_type' => gettype($details['installments_number'] ?? null)
                        ]);
                        
                        Log::info('💳 TRANSBANK PAYMENTS TABLE UPDATE - SQL Query', [
                            'payment_id' => $payment->id,
                            'table' => 'payments',
                            'fields_to_update' => [
                                'status' => $updateData['status'],
                                'authorization_code' => $updateData['authorization_code'],
                                'card_type' => $updateData['card_type'],
                                'installments_number' => $updateData['installments_number'],
                                'gateway_response' => 'JSON_DATA',
                                'transaction_date' => $updateData['transaction_date']->format('Y-m-d H:i:s')
                            ],
                            'installments_number_raw' => $updateData['installments_number'],
                            'installments_number_is_null' => is_null($updateData['installments_number'])
                        ]);
                        
                        $payment->update($updateData);
                        
                        Log::info('💳 TRANSBANK AFTER UPDATE - Payment refreshed', [
                            'payment_id' => $payment->id,
                            'status' => $payment->status,
                            'authorization_code' => $payment->authorization_code,
                            'card_type' => $payment->card_type,
                            'installments_number' => $payment->installments_number,
                            'installments_number_type' => gettype($payment->installments_number)
                        ]);
                        
                        Log::info('💳 TRANSBANK PAYMENT UPDATED - Payment record updated', [
                            'payment_id' => $payment->id,
                            'update_data' => $updateData
                        ]);

                        // Actualizar OrderDetail
                        $orderDetail = $payment->orderDetail;
                        if ($orderDetail) {
                            $orderDetail->update([
                                'is_paid' => true,
                                'status' => 'paid',
                                'paid_at' => now()->setTimezone('America/Santiago'),
                                'transaction_id' => $token,
                                'gateway_response' => $confirmResult,
                            ]);
                            
                            Log::info('💳 TRANSBANK ORDER DETAIL UPDATED - OrderDetail updated', [
                                'order_detail_id' => $orderDetail->id,
                                'is_paid' => true,
                                'status' => 'paid'
                            ]);
                            
                            // Si es una cuota, marcarla como pagada
                            if ($orderDetail->installment_number >= 1) {
                                $this->markInstallmentAsPaid($orderDetail);
                            }
                            
                            // Recalcular estado de la orden
                            if ($orderDetail->order) {
                                $orderDetail->order->refreshStatus();
                            }
                        }
                        
                        Log::info('💳 TRANSBANK PAYMENT CONFIRMED - Redirecting to success', [
                            'order_detail_id' => $orderDetailId
                        ]);
                        
                        return redirect()->route('payment.success', ['orderDetailId' => $orderDetailId]);
                    } else {
                    Log::info('💳 TRANSBANK PAYMENT FAILED - Updating status to failed', [
                        'order_detail_id' => $orderDetailId,
                        'confirm_result' => $confirmResult
                    ]);
                    
                    // Si el pago fue rechazado, actualizar el estado y redirigir a fallo
                    $payment->update([
                        'status' => 'failed',
                        'gateway_response' => $confirmResult,
                    ]);

                    $orderDetail = $payment->orderDetail;
                    if ($orderDetail) {
                        $orderDetail->update([
                            'status' => 'failed',
                            'is_paid' => false,
                        ]);
                    }
                    
                    return redirect()->route('payment.failure', ['orderDetailId' => $orderDetailId])
                        ->with('status', 'rejected');
                }
            } else {
                Log::error('💳 TRANSBANK PAYMENT NOT FOUND - No payment found for token', [
                    'token' => $token,
                    'order_detail_id' => $orderDetailId,
                    'search_criteria' => [
                        'token' => $token,
                        'external_payment_id' => $token
                    ]
                ]);
            }
            }
        }
        
        $data = $this->getSpinnerDataService->execute($request, (int) $orderDetailId);
        
        return Inertia::render('Payment/PaymentSpinner', $data);
    }

    /**
     * Marcar cuota como pagada (copiado de Khipu)
     */
    private function markInstallmentAsPaid($orderDetail): void
    {
        try {
            // Buscar la cuota correspondiente usando el installment_number
            $installment = \App\Models\Installment::where('installment_number', $orderDetail->installment_number)
                ->whereHas('installmentPlan', function($query) use ($orderDetail) {
                    $query->where('participant_id', $orderDetail->order->participant_id)
                          ->where('program_id', $orderDetail->order->program_id);
                })
                ->first();

            if ($installment) {
                $installment->markAsPaid(
                    $orderDetail->order_id,
                    $orderDetail->id,
                    $orderDetail->payments()->latest()->first()->id
                );

                Log::info('💳 TRANSBANK INSTALLMENT MARKED AS PAID', [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'order_id' => $orderDetail->order_id,
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $orderDetail->payments()->latest()->first()->id
                ]);
            } else {
                Log::warning('💳 TRANSBANK INSTALLMENT NOT FOUND', [
                    'installment_number' => $orderDetail->installment_number,
                    'participant_id' => $orderDetail->order->participant_id,
                    'program_id' => $orderDetail->order->program_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('💳 TRANSBANK ERROR MARKING INSTALLMENT AS PAID', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetail->id,
                'installment_number' => $orderDetail->installment_number
            ], $e);
        }
    }

    /**
     * Confirmar pago unificado
     */
    public function confirmPayment(Request $request)
    {
        $result = $this->processPaymentConfirmationService->execute($request);

        return response()->json($result);
    }

    /**
     * Vista de éxito unificada
     */
    public function showSuccess(Request $request, $orderDetailId)
    {
        try {
            $data = $this->getSuccessDataService->execute((int) $orderDetailId);

            return Inertia::render('Payment/PaymentSuccess', $data);
        } catch (\Exception $e) {
            Log::error('PaymentConfirmationController: Error showing success', [
                'error' => $e->getMessage(),
                'order_detail_id' => $orderDetailId,
            ]);
            
            return redirect()->route('payment.failure', $orderDetailId)
                ->with('error', 'Error al mostrar la confirmación del pago.');
        }
    }

    /**
     * Vista de error unificada
     */
    public function showFailure(Request $request, $orderDetailId)
    {
        $data = $this->getFailureDataService->execute($request, (int) $orderDetailId);

        return Inertia::render('Payment/PaymentFailure', $data);
    }

    /**
     * Manejar notificaciones webhook de VirtualPOS
     */
    public function handleVirtualPosWebhook(Request $request, $orderDetailId)
    {
        try {
            // Log detallado de la notificación recibida
            Log::info('VirtualPOS webhook received - Full details', [
                'order_detail_id' => $orderDetailId,
                'all_request_data' => $request->all(),
                'headers' => $request->headers->all(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'query_params' => $request->query(),
                'body_params' => $request->post(),
                'json_data' => $request->json() ? $request->json()->all() : null,
                'notification_data' => $request->all()
            ]);

            $notificationData = $request->all();
            $paymentId = $notificationData['payment_id'] ?? $notificationData['id'] ?? $notificationData['uuid'] ?? null;

            if (!$paymentId) {
                Log::error('VirtualPOS webhook: No payment_id provided', [
                    'order_detail_id' => $orderDetailId,
                    'data' => $notificationData
                ]);
                return response()->json(['error' => 'No payment_id provided'], 400);
            }

            // Buscar el pago en la base de datos usando el payment_id (uuid)
            // Para VirtualPOS, el UUID se guarda en la columna 'token'
            $payment = \App\Models\Payment::where('token', $paymentId)
                ->orWhere('external_payment_id', $paymentId)
                ->first();
            
            if (!$payment) {
                // Log adicional para debug: buscar todos los pagos relacionados con este order_detail
                $allPayments = \App\Models\Payment::where('order_detail_id', $orderDetailId)->get();
                Log::error('VirtualPOS webhook: Payment not found - Debug info', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                    'all_payments_for_order_detail' => $allPayments->map(function($p) {
                        return [
                            'id' => $p->id,
                            'external_payment_id' => $p->external_payment_id,
                            'token' => $p->token,
                            'status' => $p->status,
                            'amount' => $p->amount
                        ];
                    })
                ]);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Procesar la notificación usando el servicio VirtualPOS
            $virtualPosService = app(\App\Services\Client\PaymentGateway\VirtualPosService::class);
            
            // Si el webhook no incluye status, consultar el estado del pago
            $status = $notificationData['status'] ?? null;
            if (!$status) {
                Log::info('VirtualPOS webhook: No status provided, consulting payment status', [
                    'payment_id' => $paymentId
                ]);
                
                $confirmResult = $virtualPosService->confirmTransaction($paymentId);
                if ($confirmResult['success']) {
                    $status = $confirmResult['status'];
                    $notificationData['status'] = $status;
                    $notificationData['amount'] = $confirmResult['amount'];
                    $notificationData['authorization_code'] = $confirmResult['authorization_code'];
                    $notificationData['installments'] = $confirmResult['installments'] ?? null;
                    $notificationData['installment_amount'] = $confirmResult['installment_amount'] ?? null;
                } else {
                    // Verificar si el resultado contiene información de estado rechazado
                    $responseStatus = $confirmResult['full_response']['payment']['order']['status'] ?? null;
                    
                    if (in_array($responseStatus, ['rechazado', 'cancelado', 'cancelled', 'rejected', 'failed'])) {
                        // Actualizar el estado del pago a fallido
                        $payment->update([
                            'status' => 'failed',
                            'gateway_response' => $confirmResult,
                        ]);

                        $orderDetail = $payment->orderDetail;
                        if ($orderDetail) {
                            $orderDetail->update([
                                'status' => 'failed',
                                'is_paid' => false,
                            ]);
                        }
                        
                        Log::info('VirtualPOS payment marked as failed', [
                            'order_detail_id' => $orderDetailId,
                            'payment_id' => $paymentId,
                            'status' => $responseStatus,
                            'result' => $confirmResult
                        ]);
                        
                        // Redirigir directamente a la página de fallo
                        return redirect()->route('payment.failure', ['orderDetailId' => $orderDetailId])
                            ->with('status', 'canceled');
                    } else {
                        Log::error('VirtualPOS webhook: Failed to confirm transaction', [
                            'payment_id' => $paymentId,
                            'result' => $confirmResult
                        ]);
                        return response()->json(['error' => 'Failed to confirm transaction'], 400);
                    }
                }
            }
            
            $result = $virtualPosService->processNotification($notificationData);

            if ($result['success']) {
                // Usar el servicio centralizado para procesar el pago exitoso
                // Esto evitará duplicación de emails y asegurará consistencia
                $this->paymentConfirmationService->confirmPayment(
                    $orderDetailId,
                    'virtualpos',
                    $result
                );

                Log::info('VirtualPOS webhook processed successfully', [
                    'order_detail_id' => $orderDetailId,
                    'payment_id' => $paymentId,
                    'status' => $status,
                    'result' => $result
                ]);

                return response()->json(['success' => true]);
            } else {
                // Si el pago fue rechazado/cancelado, actualizar el estado y redirigir
                if (in_array($status, ['rechazado', 'cancelado', 'cancelled', 'rejected', 'failed'])) {
                    $payment->update([
                        'status' => 'failed',
                        'gateway_response' => $result,
                    ]);

                    $orderDetail = $payment->orderDetail;
                    if ($orderDetail) {
                        $orderDetail->update([
                            'status' => 'failed',
                            'is_paid' => false,
                        ]);
                    }

                    Log::info('VirtualPOS payment rejected/cancelled', [
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $paymentId,
                        'status' => $status,
                        'result' => $result
                    ]);

                    // Redirigir directamente a la página de fallo
                    return redirect()->route('payment.failure', ['orderDetailId' => $orderDetailId])
                        ->with('status', 'canceled');
                } else {
                    Log::error('VirtualPOS webhook processing failed', [
                        'order_detail_id' => $orderDetailId,
                        'payment_id' => $paymentId,
                        'status' => $status,
                        'result' => $result
                    ]);

                    return response()->json(['error' => $result['error']], 400);
                }
            }
        } catch (\Exception $e) {
            Log::error('VirtualPOS webhook error', [
                'order_detail_id' => $orderDetailId,
                'error' => $e->getMessage(),
                'data' => $request->all()
            ], $e);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

}
