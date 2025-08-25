<?php

namespace App\Services\Admin\Payments;

use App\Models\Participant;
use App\Models\Program;
use App\Models\Payment;
use App\Models\Order;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetParticipantPaymentStatusService
{
    /**
     * Obtener estado de pagos de un participante para un programa específico
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function execute(Request $request): array
    {
        $programId = $request->program_id;
        $participantId = $request->participant_id;

        try {
            // Obtener el participante
            $participant = Participant::find($participantId);
            $program = Program::find($programId);

            if (!$participant || !$program) {
                throw new \Exception('Participante o programa no encontrado');
            }

            // Calcular montos usando el helper
            try {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
                $totalAmount = $priceData['final_price'];
            } catch (\Exception $e) {
                // Fallback al precio del programa si el helper falla
                $totalAmount = $program->trip_price ?? 0;
                Log::warning('Error calculando precio con helper, usando precio del programa', [
                    'error' => $e->getMessage(),
                    'participant_id' => $participantId,
                    'program_id' => $programId
                ]);
            }

            // Inicializar variables
            $totalInstallments = 0;
            $paidInstallments = 0;
            $totalPaidAmount = 0;
            $orders = collect(); // Inicializar como colección vacía

            // Buscar planes de cuotas del participante para este programa (más confiable)
            $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->with(['installments'])
                ->get();

            foreach ($installmentPlans as $plan) {
                $totalInstallments = $plan->installments->count();
                
                foreach ($plan->installments as $installment) {
                    if ($installment->status === 'paid') {
                        $paidInstallments++;
                    }
                }
            }

            // CALCULAR EL MONTO REAL PAGADO desde la tabla payments
            $totalPaidAmount = Payment::whereHas('order', function ($q) use ($participantId, $programId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programId);
                })
                ->whereIn('status', ['completed', 'approved'])
                ->sum('amount');

            // Si no hay planes de cuotas, buscar en orders como fallback
            if ($totalInstallments == 0) {
                $orders = Order::where('participant_id', $participantId)
                    ->where('program_id', $programId)
                    ->with(['orderDetails', 'payments'])
                    ->get();

                foreach ($orders as $order) {
                    if ($order->orderDetails) {
                        $totalInstallments = $order->orderDetails->count();
                        
                        foreach ($order->orderDetails as $detail) {
                            if ($detail->is_paid) {
                                $paidInstallments++;
                            }
                        }
                    }
                }
            }

            $totalPaidAmount = round($totalPaidAmount, 2);
            $balance = max(round($totalAmount - $totalPaidAmount, 2), 0);
            $paymentPercentage = $totalAmount > 0 ? round(($totalPaidAmount / $totalAmount) * 100, 2) : 0;

            // Validar que los números sean lógicos
            if ($paidInstallments > $totalInstallments) {
                // Corregir el error lógico
                $paidInstallments = min($paidInstallments, $totalInstallments);
            }

            // Determinar estado de inscripción
            $isEnrolled = $program->course && $program->course->participants->contains($participantId);
            
            // Estado de pagos
            $paymentStatus = 'no_enrolled';
            if ($isEnrolled) {
                if ($totalPaidAmount == 0) {
                    $paymentStatus = 'no_payments';
                } elseif ($totalPaidAmount < $totalAmount) {
                    $paymentStatus = 'partial_payments';
                } else {
                    $paymentStatus = 'fully_paid';
                }
            }

            return [
                'success' => true,
                'data' => [
                    'participant' => [
                        'id' => $participant->id,
                        'name' => $participant->full_name,
                        'document_number' => $participant->document_number,
                        'email' => $participant->email,
                    ],
                    'program' => [
                        'id' => $program->id,
                        'name' => $program->name,
                        'destination' => $program->destination,
                    ],
                    'payment_info' => [
                        'total_amount' => $totalAmount,
                        'paid_amount' => $totalPaidAmount,
                        'balance' => $balance,
                        'payment_percentage' => $paymentPercentage,
                        'payment_status' => $paymentStatus,
                        'is_enrolled' => $isEnrolled,
                        'total_installments' => $totalInstallments,
                        'paid_installments' => $paidInstallments,
                        'installments_summary' => $totalInstallments > 0 ? "{$paidInstallments}/{$totalInstallments}" : "0/0",
                    ],
                    'orders' => $orders->map(function($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'total_amount' => $order->total_amount,
                            'final_amount' => $order->final_amount,
                            'total_installments' => $order->total_installments,
                            'status' => $order->status,
                            'created_at' => $order->created_at ? $order->created_at->format('d/m/Y H:i') : null,
                            'order_details' => $order->orderDetails->map(function($detail) {
                                return [
                                    'id' => $detail->id,
                                    'installment_number' => $detail->installment_number,
                                    'amount' => $detail->amount,
                                    'status' => $detail->status,
                                    'is_paid' => $detail->is_paid,
                                    'due_date' => $detail->due_date ? $detail->due_date->format('d/m/Y') : null,
                                    'paid_at' => $detail->paid_at ? $detail->paid_at->format('d/m/Y H:i') : null,
                                ];
                            }),
                            'payments' => $order->payments->map(function($payment) {
                                return [
                                    'id' => $payment->id,
                                    'amount' => $payment->amount,
                                    'status' => $payment->status,
                                    'payment_code' => $payment->payment_code,
                                    'created_at' => $payment->created_at ? $payment->created_at->format('d/m/Y H:i') : null,
                                ];
                            })
                        ];
                    })
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Error al obtener estado de pagos del participante', [
                'participant_id' => $participantId,
                'program_id' => $programId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
