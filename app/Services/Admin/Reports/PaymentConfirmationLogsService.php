<?php

namespace App\Services\Admin\Reports;

use App\Models\Payment;
use App\Models\PaymentConfirmationLog;
use App\Models\ProgramCourse;
use Illuminate\Support\Facades\DB;

class PaymentConfirmationLogsService
{
    /**
     * Obtener lista de pagos con sus logs de confirmación
     */
    public function getPaymentLogs(array $filters = []): array
    {
        $query = Payment::with([
            'orderDetail.order.participant',
            'orderDetail.order.programCourse',
            'confirmationLogs' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])
        ->whereHas('orderDetail')
        ->whereIn('status', ['completed', 'approved']);

        // Aplicar filtros
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['program_id'])) {
            $query->whereHas('orderDetail.order', function ($q) use ($filters) {
                $q->where('program_id', $filters['program_id']);
            });
        }

        if (!empty($filters['participant_search'])) {
            $search = $filters['participant_search'];
            $query->whereHas('orderDetail.order.participant', function ($q) use ($search) {
                $q->where('document_number', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', first_last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            if ($filters['status'] === 'success') {
                $query->whereHas('confirmationLogs', function ($q) {
                    $q->where('event_type', 'email_sent')
                      ->where('status', 'success');
                });
            } elseif ($filters['status'] === 'failed') {
                $query->where(function ($q) {
                    $q->whereHas('confirmationLogs', function ($subQ) {
                        $subQ->where('event_type', 'email_failed');
                    })
                    ->orWhereDoesntHave('confirmationLogs', function ($subQ) {
                        $subQ->where('event_type', 'email_sent');
                    });
                });
            }
        }

        // Filtro por tipo de pago (pasarela vs presencial/manual)
        if (!empty($filters['payment_type']) && $filters['payment_type'] !== 'all') {
            if ($filters['payment_type'] === 'gateway') {
                // Pagos por pasarela: tienen payment_gateway_id (1=Transbank, 2=Khipu, 3=VirtualPos) o tienen token
                $query->where(function ($q) {
                    $q->whereIn('payment_gateway_id', [1, 2, 3])
                      ->orWhereNotNull('token');
                });
            } elseif ($filters['payment_type'] === 'manual') {
                // Pagos presenciales/manuales: gateway_id = 4 (presencial) o sin gateway y sin token
                $query->where(function ($q) {
                    $q->where('payment_gateway_id', 4)
                      ->orWhere(function ($subQ) {
                          $subQ->whereNull('payment_gateway_id')
                               ->whereNull('token');
                      });
                });
            }
        }

        // Ordenar por fecha descendente
        $query->orderBy('created_at', 'desc');

        // Paginar
        $perPage = $filters['per_page'] ?? 25;
        $payments = $query->paginate($perPage);

        // Transformar datos para la vista
        $data = $payments->map(function ($payment) {
            return $this->transformPaymentData($payment);
        });

        return [
            'data' => $data,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ];
    }

    /**
     * Obtener detalles de logs para un pago específico
     */
    public function getPaymentLogDetails(int $paymentId): array
    {
        $payment = Payment::with([
            'orderDetail.order.participant',
            'orderDetail.order.programCourse',
            'confirmationLogs' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])->findOrFail($paymentId);

        return [
            'payment' => $this->transformPaymentData($payment),
            'logs' => $payment->confirmationLogs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'status' => $log->status,
                    'created_at' => $log->created_at->format('d/m/Y H:i:s'),
                    'error_message' => $log->error_message,
                    'file_path' => $log->file_path,
                    'file_name' => $log->file_name,
                    'bsale_document_id' => $log->bsale_document_id,
                    'bsale_number' => $log->bsale_number,
                    'email_recipient' => $log->email_recipient,
                    'email_attachments' => $log->email_attachments,
                    'details' => $log->details,
                    'triggered_by_user' => $log->triggeredByUser ? [
                        'id' => $log->triggeredByUser->id,
                        'name' => $log->triggeredByUser->name,
                    ] : null,
                ];
            }),
        ];
    }

    /**
     * Obtener lista de programas para filtro
     */
    public function getProgramsForFilter(): array
    {
        return ProgramCourse::where('active', true)
            ->orderBy('code', 'desc')
            ->get(['id', 'code', 'name', 'destination', 'departure_date'])
            ->map(function ($program) {
                $year = $program->departure_date ? $program->departure_date->format('Y') : 'N/A';
                return [
                    'id' => $program->id,
                    'code' => $program->code,
                    'name' => $program->name,
                    'label' => ($program->code ? $program->code . ' - ' : '') . $program->name . ' (' . $year . ')',
                ];
            })
            ->toArray();
    }

    /**
     * Transformar datos del payment para la vista
     */
    private function transformPaymentData($payment): array
    {
        $orderDetail = $payment->orderDetail;
        $order = $orderDetail->order;
        $participant = $order->participant;
        $program = $order->programCourse;

        // Resumir eventos
        $events = [
            'payment_receipt' => false,
            'contract' => false,
            'bsale_invoice' => false,
            'email_sent' => false,
            'has_failed' => false,
        ];

        foreach ($payment->confirmationLogs as $log) {
            if ($log->status === 'failed') {
                $events['has_failed'] = true;
            }

            switch ($log->event_type) {
                case 'payment_receipt_generated':
                    $events['payment_receipt'] = $log->status === 'success';
                    break;
                case 'contract_generated':
                    $events['contract'] = $log->status === 'success';
                    break;
                case 'bsale_invoice_generated':
                    $events['bsale_invoice'] = $log->status === 'success';
                    break;
                case 'email_sent':
                case 'email_resent':
                    $events['email_sent'] = $log->status === 'success';
                    break;
            }
        }

        // Determinar tipo de pago
        $isGatewayPayment = in_array($payment->payment_gateway_id, [1, 2, 3]) || !empty($payment->token);
        $paymentType = $isGatewayPayment ? 'gateway' : 'manual';
        $paymentTypeLabel = $isGatewayPayment ? 'Pasarela' : 'Manual/Presencial';

        return [
            'payment_id' => $payment->id,
            'order_number' => $order->order_number,
            'payment_date' => $payment->created_at->format('d/m/Y H:i'),
            'participant' => [
                'name' => $participant->full_name ?? 'N/A',
                'document' => $participant->document_number ?? 'N/A',
            ],
            'program' => [
                'name' => $program->name ?? 'N/A',
                'destination' => $program->destination ?? 'N/A',
            ],
            'amount' => number_format($payment->amount, 0, ',', '.'),
            'payment_type' => $paymentType,
            'payment_type_label' => $paymentTypeLabel,
            'events' => $events,
            'email_recipient' => $orderDetail->email,
            'can_resend' => $events['payment_receipt'] || $events['contract'] || $events['bsale_invoice'],
        ];
    }
}
