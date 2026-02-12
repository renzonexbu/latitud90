<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Services\Client\Integration\BsaleService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Log;

class ReconfirmPaymentService
{
    use SystemLogging;

    private const KHIPU_GATEWAY_ID = 2;

    public function __construct(
        private VirtualPosService $virtualPosService,
        private KhipuService $khipuService,
        private BsaleService $bsaleService,
        private SuccessPaymentEmailService $emailService
    ) {}

    /**
     * Reconfirmar un pago consultando la pasarela correspondiente (VirtualPOS o Khipu)
     */
    public function execute(Payment $payment, bool $skipEmail = false, bool $skipBsale = false): array
    {
        try {
            $isKhipu = $payment->payment_gateway_id === self::KHIPU_GATEWAY_ID;

            $this->logInfo('ReconfirmPaymentService: Iniciando reconfirmación', [
                'payment_id' => $payment->id,
                'current_status' => $payment->status,
                'gateway' => $isKhipu ? 'khipu' : 'virtualpos',
                'skip_email' => $skipEmail,
                'skip_bsale' => $skipBsale,
            ]);

            // Consultar estado en la pasarela correspondiente
            $gatewayResult = $isKhipu
                ? $this->queryKhipu($payment)
                : $this->queryVirtualPos($payment);

            // Si hubo error en la consulta, retornar
            if (isset($gatewayResult['error'])) {
                return $gatewayResult;
            }

            $gatewayStatus = $gatewayResult['status'];
            $isApproved = $gatewayResult['is_approved'];
            $isRejected = $gatewayResult['is_rejected'];
            $result = $gatewayResult['raw_result'];

            // Determinar nuevo estado
            $newStatus = 'pending';
            if ($isApproved) {
                $newStatus = 'completed';
            } elseif ($isRejected) {
                $newStatus = 'failed';
            }

            $currentStatus = $payment->status;
            $statusChanged = $currentStatus !== $newStatus && $currentStatus !== 'completed';

            // Preparar datos de actualización
            $updateData = [
                'gateway_response' => $result['full_response'] ?? $result,
                'authorization_code' => $result['authorization_code'] ?? $payment->authorization_code,
            ];

            // Actualizar transaction_date si viene de la pasarela y el pago fue aprobado
            if ($isApproved && !empty($result['transaction_date'])) {
                $updateData['transaction_date'] = $result['transaction_date'];
            }

            if ($statusChanged) {
                $updateData['status'] = $newStatus;

                // Si el pago fue aprobado, asegurar que document_type esté establecido
                if ($isApproved && empty($payment->document_type)) {
                    $updateData['document_type'] = PaymentDocumentTypeHelper::determineDocumentType(
                        $payment->orderDetail->order->program_id ?? null
                    );
                }
            }

            $payment->update($updateData);
            $payment->refresh();

            // Actualizar OrderDetail si existe
            $orderDetail = $payment->orderDetail;
            if ($orderDetail && $statusChanged) {
                $orderDetail->update([
                    'status' => $isApproved ? 'paid' : ($isRejected ? 'failed' : 'pending'),
                    'is_paid' => $isApproved,
                    'paid_at' => $isApproved ? ($orderDetail->paid_at ?? now()) : null,
                    'gateway_response' => $result['full_response'] ?? $result,
                ]);

                // Actualizar Order si existe
                if ($orderDetail->order) {
                    $orderDetail->order->refreshStatus();
                }

                // Si el pago fue aprobado y es una cuota, marcarla como pagada
                if ($isApproved && $orderDetail->installment_number >= 1) {
                    $this->markInstallmentAsPaid($orderDetail, $payment);
                }
            }

            // Si el pago fue aprobado, ejecutar post-procesamiento
            $bsaleGenerated = false;
            $emailSent = false;

            if ($isApproved && $orderDetail) {
                // Generar boleta Bsale
                if (!$skipBsale) {
                    $bsaleGenerated = $this->generateBsaleInvoice($payment, $orderDetail);
                }

                // Enviar correo de confirmación
                if (!$skipEmail) {
                    $emailSent = $this->sendConfirmationEmail($payment, $orderDetail);
                }
            }

            $this->logInfo('ReconfirmPaymentService: Reconfirmación completada', [
                'payment_id' => $payment->id,
                'old_status' => $currentStatus,
                'new_status' => $newStatus,
                'gateway_status' => $gatewayStatus,
                'status_changed' => $statusChanged,
                'bsale_generated' => $bsaleGenerated,
                'email_sent' => $emailSent,
            ]);

            return [
                'success' => true,
                'message' => $this->buildSuccessMessage($currentStatus, $newStatus, $statusChanged, $bsaleGenerated, $emailSent),
                'status' => $newStatus,
                'status_changed' => $statusChanged,
                'gateway_status' => $gatewayStatus,
                'bsale_generated' => $bsaleGenerated,
                'email_sent' => $emailSent,
                'payment' => $payment->fresh(['orderDetail', 'order']),
            ];

        } catch (\Exception $e) {
            $this->logError('ReconfirmPaymentService: Error en reconfirmación', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);

            return [
                'success' => false,
                'message' => 'Error al reconfirmar el pago: ' . $e->getMessage(),
                'status' => 'error',
            ];
        }
    }

    /**
     * Consultar estado del pago en Khipu (via VirtualPos API)
     */
    private function queryKhipu(Payment $payment): array
    {
        $paymentId = $payment->external_payment_id;
        if (empty($paymentId)) {
            return [
                'error' => true,
                'success' => false,
                'message' => 'El pago Khipu no tiene external_payment_id para consultar',
                'status' => 'error',
            ];
        }

        $result = $this->khipuService->getPaymentStatus($paymentId);

        if (isset($result['error']) && !isset($result['success'])) {
            return [
                'error' => true,
                'success' => false,
                'message' => 'Error al consultar Khipu: ' . ($result['error'] ?? 'desconocido'),
                'status' => 'error',
            ];
        }

        $status = $result['status'] ?? 'unknown';
        $isApproved = $result['success'] ?? false;
        $isRejected = in_array($status, ['rejected', 'cancelled', 'error']);

        return [
            'status' => $status,
            'is_approved' => $isApproved,
            'is_rejected' => $isRejected,
            'raw_result' => [
                'full_response' => $result['data'] ?? $result,
                'authorization_code' => $result['auth_code'] ?? null,
                'transaction_date' => $result['transaction_date'] ?? null,
            ],
        ];
    }

    /**
     * Consultar estado del pago en VirtualPOS
     */
    private function queryVirtualPos(Payment $payment): array
    {
        $token = $payment->token;
        if (empty($token)) {
            return [
                'error' => true,
                'success' => false,
                'message' => 'El pago no tiene token de VirtualPOS para consultar',
                'status' => 'error',
            ];
        }

        $result = $this->virtualPosService->confirmTransaction($token);

        if (!isset($result['success'])) {
            return [
                'error' => true,
                'success' => false,
                'message' => 'Error al consultar VirtualPOS',
                'status' => 'error',
            ];
        }

        if (isset($result['error']) && str_contains($result['error'], 'no encontrada')) {
            return [
                'error' => true,
                'success' => false,
                'message' => 'Transacción no encontrada en VirtualPOS',
                'status' => 'not_found',
                'virtualpos_response' => $result,
            ];
        }

        $status = $result['status'] ?? 'unknown';
        $isApproved = in_array($status, VirtualPosService::APPROVED_STATUSES);
        $isRejected = in_array($status, VirtualPosService::REJECTED_STATUSES);

        return [
            'status' => $status,
            'is_approved' => $isApproved,
            'is_rejected' => $isRejected,
            'raw_result' => $result,
        ];
    }

    /**
     * Marcar cuota como pagada
     */
    private function markInstallmentAsPaid(OrderDetail $orderDetail, Payment $payment): void
    {
        try {
            $installment = \App\Models\Installment::where('installment_number', $orderDetail->installment_number)
                ->whereHas('installmentPlan', function($query) use ($orderDetail) {
                    $query->where('participant_id', $orderDetail->order->participant_id)
                          ->where('program_id', $orderDetail->order->program_id);
                })
                ->first();

            if ($installment && !$installment->is_paid) {
                $installment->markAsPaid(
                    $orderDetail->order_id,
                    $orderDetail->id,
                    $payment->id
                );

                $this->logInfo('ReconfirmPaymentService: Cuota marcada como pagada', [
                    'installment_id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                ]);
            }
        } catch (\Exception $e) {
            $this->logWarning('ReconfirmPaymentService: Error marcando cuota como pagada', [
                'order_detail_id' => $orderDetail->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generar boleta en Bsale
     */
    private function generateBsaleInvoice(Payment $payment, OrderDetail $orderDetail): bool
    {
        try {
            // Verificar si ya tiene boleta generada
            if (!empty($payment->bsale_number)) {
                $this->logInfo('ReconfirmPaymentService: Boleta ya existe', [
                    'payment_id' => $payment->id,
                    'bsale_number' => $payment->bsale_number,
                ]);
                return true;
            }

            // CRÍTICO: Solo generar boleta si el pago está CONFIRMADO
            $confirmedStatuses = ['completed', 'approved'];
            if (!in_array($payment->status, $confirmedStatuses)) {
                $this->logWarning('ReconfirmPaymentService: NO se genera boleta - pago NO está confirmado', [
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                    'required_statuses' => $confirmedStatuses,
                ]);
                return false;
            }

            // Verificar si el document_type permite generar boleta (solo B2)
            if ($payment->document_type !== 'B2') {
                $this->logInfo('ReconfirmPaymentService: No genera boleta - document_type no es B2', [
                    'payment_id' => $payment->id,
                    'document_type' => $payment->document_type,
                ]);
                return false;
            }

            $bsaleResult = $this->bsaleService->generateInvoice($orderDetail, $payment);

            if ($bsaleResult) {
                $payment->refresh();
                $this->logInfo('ReconfirmPaymentService: Boleta generada', [
                    'payment_id' => $payment->id,
                    'bsale_number' => $payment->bsale_number,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logError('ReconfirmPaymentService: Error generando boleta', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);
            return false;
        }
    }

    /**
     * Enviar correo de confirmación
     */
    private function sendConfirmationEmail(Payment $payment, OrderDetail $orderDetail): bool
    {
        try {
            // Verificar si ya se envió el email
            if ($payment->email_sent) {
                $this->logInfo('ReconfirmPaymentService: Email ya enviado anteriormente', [
                    'payment_id' => $payment->id,
                ]);
                return true;
            }

            // Verificar que el pago esté completado
            if (!in_array($payment->status, ['completed', 'approved'])) {
                $this->logWarning('ReconfirmPaymentService: No se envía email - estado del pago no válido', [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                ]);
                return false;
            }

            $emailSent = $this->emailService->sendSuccessPaymentEmail($orderDetail, $payment);

            if ($emailSent) {
                $payment->update(['email_sent' => true]);
                $this->logInfo('ReconfirmPaymentService: Email enviado', [
                    'payment_id' => $payment->id,
                    'recipient' => $orderDetail->email,
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logError('ReconfirmPaymentService: Error enviando email', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);
            return false;
        }
    }

    /**
     * Construir mensaje de éxito
     */
    private function buildSuccessMessage(string $oldStatus, string $newStatus, bool $statusChanged, bool $bsaleGenerated, bool $emailSent): string
    {
        $messages = [];

        if ($statusChanged) {
            $statusLabels = [
                'completed' => 'Pagado',
                'failed' => 'Rechazado',
                'pending' => 'Pendiente',
            ];
            $messages[] = "Estado actualizado a: " . ($statusLabels[$newStatus] ?? $newStatus);
        } else {
            $messages[] = "Estado sin cambios ({$newStatus})";
        }

        if ($newStatus === 'completed') {
            if ($bsaleGenerated) {
                $messages[] = "Boleta generada";
            }
            if ($emailSent) {
                $messages[] = "Correo enviado";
            }
        }

        return implode('. ', $messages);
    }
}
