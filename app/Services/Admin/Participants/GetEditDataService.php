<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\Program;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Facades\Log;

class GetEditDataService
{
    /**
     * Obtener datos necesarios para editar un participante
     *
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function execute(int $id): array
    {
        try {
            // Buscar el participante
            $participant = Participant::findOrFail($id);

            // Cargar relaciones
            $participant->load(['courses', 'courses.institution', 'courses.program', 'emergencyContacts']);

            // Cargar participant_programs con sus descuentos
            $participantPrograms = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                ->with(['program', 'discounts'])
                ->get();

            // Obtener datos de programas del participante con precios calculados
            $participantProgramsData = $this->getParticipantProgramsData($participant, $participantPrograms);

            // Enriquecer participantPrograms con datos de cuotas
            $participantProgramsWithDiscounts = $participantPrograms->map(function ($participantProgram) use ($participant) {
                // Buscar planes de cuotas del participante para este programa
                $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participant->id)
                    ->where('program_id', $participantProgram->program_id)
                    ->with(['installments' => function ($query) {
                        $query->orderBy('installment_number', 'asc');
                    }])
                    ->get();

                $installmentPlan = null;
                $totalInstallments = 0;
                $paidInstallments = 0;

                foreach ($installmentPlans as $plan) {
                    if ($plan->status === 'active') {
                        $installmentPlan = $plan;
                        $totalInstallments = $plan->installments->count();
                        
                        // Contar cuotas pagadas
                        foreach ($plan->installments as $installment) {
                            if ($installment->status === 'paid' || !is_null($installment->payment_id)) {
                                $paidInstallments++;
                            }
                        }
                        break; // Solo tomar el primer plan activo
                    }
                }

                // Agregar datos de cuotas al participantProgram
                $participantProgram->installment_plan = $installmentPlan ? [
                    'id' => $installmentPlan->id,
                    'total_amount' => $installmentPlan->total_amount,
                    'total_installments' => $installmentPlan->total_installments,
                    'status' => $installmentPlan->status,
                    'start_date' => $installmentPlan->start_date,
                    'end_date' => $installmentPlan->end_date,
                    'installments' => $installmentPlan->installments->map(function ($installment) {
                        return [
                            'id' => $installment->id,
                            'installment_number' => $installment->installment_number,
                            'amount' => $installment->amount,
                            'due_date' => $installment->due_date,
                            'status' => $installment->status,
                            'paid_at' => $installment->paid_at,
                            'payment_id' => $installment->payment_id,
                            'payment_order_id' => $installment->payment_order_id,
                            'payment_order_detail_id' => $installment->payment_order_detail_id,
                            'adjusted_at' => $installment->adjusted_at,
                            'adjustment_reason' => $installment->adjustment_reason,
                            'notes' => $installment->notes,
                        ];
                    })
                ] : null;

                $participantProgram->total_installments = $totalInstallments;
                $participantProgram->paid_installments = $paidInstallments;

                return $participantProgram;
            });

            return [
                'participant' => $participant,
                'participantPrograms' => $participantProgramsData,
                'participantProgramsWithDiscounts' => $participantProgramsWithDiscounts,
            ];
        } catch (\Exception $e) {
            Log::error('Error al obtener datos para editar participante', [
                'participant_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Obtener datos de programas del participante con precios calculados
     *
     * @param Participant $participant
     * @param \Illuminate\Support\Collection $participantPrograms
     * @return \Illuminate\Support\Collection
     */
    private function getParticipantProgramsData(Participant $participant, $participantPrograms)
    {
        return Program::whereHas('course.participants', function ($query) use ($participant) {
            $query->where('participants.id', $participant->id);
        })
            ->with(['course' => function ($q) use ($participant) {
                $q->with(['institution', 'participants' => function ($qp) use ($participant) {
                    $qp->where('participants.id', $participant->id);
                }]);
            }])
            ->get()
            ->map(function ($program) use ($participant, $participantPrograms) {
                // Usar el helper para calcular el precio final con descuentos
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);

                // Pagos aprobados/completados del participante para este programa
                $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                    $q->where('participant_id', $participant->id)
                        ->where('program_id', $program->id);
                })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');
                $paidAmount = round($paidAmount, 2);
                $balance = max(round($priceData['final_price'] - $paidAmount, 2), 0);
                $paymentPercentage = ($priceData['final_price'] > 0)
                    ? round(($paidAmount / $priceData['final_price']) * 100, 0)
                    : 0;

                // Calcular información de cuotas
                $totalInstallments = 0;
                $paidInstallments = 0;
                $installmentsSummary = null;
                $installmentPlan = null;

                // Buscar planes de cuotas del participante para este programa
                $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->with(['installments' => function ($query) {
                        $query->orderBy('installment_number', 'asc');
                    }])
                    ->get();

                foreach ($installmentPlans as $plan) {
                    $totalInstallments = $plan->installments->count();
                    
                    foreach ($plan->installments as $installment) {
                        if ($installment->status === 'paid') {
                            $paidInstallments++;
                        }
                    }

                    // Guardar el primer plan activo para mostrar en el frontend
                    if (!$installmentPlan && $plan->status === 'active') {
                        $installmentPlan = $plan;
                    }
                }

                // Si no hay planes de cuotas, buscar en orders como fallback
                if ($totalInstallments == 0) {
                    $orders = \App\Models\Order::where('participant_id', $participant->id)
                        ->where('program_id', $program->id)
                        ->with(['orderDetails'])
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

                // Crear resumen de cuotas si hay cuotas
                if ($totalInstallments > 0) {
                    $installmentsSummary = "{$paidInstallments}/{$totalInstallments}";
                }

                $array = $program->toArray();
                $array['participant_amount'] = $priceData['base_price']; // precio base por participante
                $array['participant_adjustments'] = $priceData['adjustments']; // ajuste del pivote
                $array['participant_total_due'] = $priceData['final_price']; // total a pagar (base + ajuste - descuentos)
                $array['paidAmount'] = $paidAmount;
                $array['participant_balance'] = $balance;
                $array['paymentPercentage'] = $paymentPercentage;
                $array['total_installments'] = $totalInstallments;
                $array['paid_installments'] = $paidInstallments;
                $array['installments_summary'] = $installmentsSummary;
                
                // Agregar el plan de cuotas completo si existe
                if ($installmentPlan) {
                    $array['installment_plan'] = [
                        'id' => $installmentPlan->id,
                        'total_amount' => $installmentPlan->total_amount,
                        'total_installments' => $installmentPlan->total_installments,
                        'status' => $installmentPlan->status,
                        'start_date' => $installmentPlan->start_date,
                        'end_date' => $installmentPlan->end_date,
                        'installments' => $installmentPlan->installments->map(function ($installment) {
                            return [
                                'id' => $installment->id,
                                'installment_number' => $installment->installment_number,
                                'amount' => $installment->amount,
                                'due_date' => $installment->due_date,
                                'status' => $installment->status,
                                'paid_at' => $installment->paid_at,
                                'payment_id' => $installment->payment_id,
                                'payment_order_id' => $installment->payment_order_id,
                                'payment_order_detail_id' => $installment->payment_order_detail_id,
                                'adjusted_at' => $installment->adjusted_at,
                                'adjustment_reason' => $installment->adjustment_reason,
                                'notes' => $installment->notes,
                            ];
                        })
                    ];
                }
                
                return $array;
            });
    }
}
