<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Models\OrderDetail;
use App\Services\Client\PaymentGateway\VirtualPosService;
use App\Services\Client\Integration\BsaleService;
use App\Services\Mail\SuccessPaymentEmailService;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\Log;

class ReconfirmPaymentService
{
    use SystemLogging;

    public function __construct(
        private VirtualPosService $virtualPosService,
        private BsaleService $bsaleService,
        private SuccessPaymentEmailService $emailService
    ) {}

    /**
     * Reconfirmar un pago consultando VirtualPOS
     */
    public function execute(Payment $payment, bool $skipEmail = false, bool $skipBsale = false): array
    {
        try {
            $this->logInfo('ReconfirmPaymentService: Iniciando reconfirmación', [
                'payment_id' => $payment->id,
                'current_status' => $payment->status,
                'skip_email' => $skipEmail,
                'skip_bsale' => $skipBsale,
            ]);

            // Verificar que el pago tenga token
            $token = $payment->token;
            if (empty($token)) {
                return [
                    'success' => false,
                    'message' => 'El pago no tiene token de VirtualPOS para consultar',
                    'status' => 'error',
                ];
            }

            // Consultar estado en VirtualPOS
            $result = $this->virtualPosService->confirmTransaction($token);

            if (!isset($result['success'])) {
                return [
                    'success' => false,
                    'message' => 'Error al consultar VirtualPOS',
                    'status' => 'error',
                ];
            }

            // Si no se encontró la transacción
            if (isset($result['error']) && str_contains($result['error'], 'no encontrada')) {
                return [
                    'success' => false,
                    'message' => 'Transacción no encontrada en VirtualPOS',
                    'status' => 'not_found',
                    'virtualpos_response' => $result,
                ];
            }

            $virtualPosStatus = $result['status'] ?? 'unknown';
            $isApproved = in_array($virtualPosStatus, VirtualPosService::APPROVED_STATUSES);
            $isRejected = in_array($virtualPosStatus, VirtualPosService::REJECTED_STATUSES);

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
                'virtualpos_status' => $virtualPosStatus,
                'status_changed' => $statusChanged,
                'bsale_generated' => $bsaleGenerated,
                'email_sent' => $emailSent,
            ]);

            return [
                'success' => true,
                'message' => $this->buildSuccessMessage($currentStatus, $newStatus, $statusChanged, $bsaleGenerated, $emailSent),
                'status' => $newStatus,
                'status_changed' => $statusChanged,
                'virtualpos_status' => $virtualPosStatus,
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
