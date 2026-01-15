<?php

namespace App\Services\Admin\Payments;

use App\Models\Participant;
use App\Models\ProgramCourse;
use App\Models\Payment;
use App\Models\Order;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetParticipantPaymentStatusService
{
    use AdminLogging;
    /**
     * Obtener estado de pagos de un participante para un programa específico
     * program_id ahora apunta a program_courses (instancias específicas)
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function execute(Request $request): array
    {
        $programCourseId = $request->program_id; // program_id ahora es program_course_id
        $participantId = $request->participant_id;

        try {
            // Obtener el participante y el program_course (instancia específica)
            $participant = Participant::find($participantId);
            $programCourse = ProgramCourse::with(['program', 'course'])->find($programCourseId);

            if (!$participant || !$programCourse) {
                throw new \Exception('Participante o programa no encontrado');
            }

            // Calcular montos usando el helper
            try {
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $totalAmount = $priceData['final_price'];
            } catch (\Exception $e) {
                // Fallback al precio del programa si el helper falla
                $totalAmount = $programCourse->trip_price ?? 0;
                Log::warning('Error calculando precio con helper, usando precio del programa', [
                    'error' => $e->getMessage(),
                    'participant_id' => $participantId,
                    'program_course_id' => $programCourseId
                ]);
            }

            // Inicializar variables
            $totalInstallments = 0;
            $paidInstallments = 0;
            $totalPaidAmount = 0;
            $orders = collect(); // Inicializar como colección vacía

            // Buscar planes de cuotas del participante para este programa (más confiable)
            // program_id en installment_plans ahora apunta a program_courses
            $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participantId)
                ->where('program_id', $programCourseId)
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
            // orders.program_id ahora apunta a program_courses
            $totalPaidAmount = Payment::whereHas('order', function ($q) use ($participantId, $programCourseId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programCourseId);
                })
                ->whereIn('status', ['completed', 'approved'])
                ->sum('amount');

            // Agregar también las cuotas pagadas de suscripciones
            $subscriptionPayments = \App\Models\Installment::whereHas('installmentPlan', function ($q) use ($participantId, $programCourseId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programCourseId);
                })
                ->where('status', 'paid')
                ->sum('amount');

            $totalPaidAmount += $subscriptionPayments;

            // Si no hay planes de cuotas, buscar en orders como fallback
            if ($totalInstallments == 0) {
                $orders = Order::where('participant_id', $participantId)
                    ->where('program_id', $programCourseId)
                    ->with(['orderDetails', 'payments'])
                    ->get();

                foreach ($orders as $order) {
                    // Si la orden tiene total_installments = 0, es un sistema sin cuotas (pagos presenciales)
                    // No contar los OrderDetails como cuotas, solo como pagos para el porcentaje
                    if ($order->total_installments == 0) {
                        continue; // No contar cuotas para órdenes presenciales
                    }

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
            $isEnrolled = $programCourse->course && $programCourse->course->participants->contains($participantId);

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

            // Log the payment status view
            $this->logView(
                'payments',
                'ParticipantPaymentStatus',
                $participant->id,
                "Consulta de estado de pagos: {$participant->first_name} {$participant->first_last_name} - Programa: {$programCourse->name}",
                [
                    'participant_id' => $participant->id,
                    'program_course_id' => $programCourse->id,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $totalPaidAmount,
                    'balance' => $balance,
                    'payment_percentage' => $paymentPercentage,
                    'payment_status' => $paymentStatus,
                    'is_enrolled' => $isEnrolled,
                ]
            );

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
                        'id' => $programCourse->id,
                        'name' => $programCourse->name,
                        'destination' => $programCourse->program->destination ?? '',
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
                'program_course_id' => $programCourseId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
