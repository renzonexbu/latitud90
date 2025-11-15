<?php

namespace App\Services\Admin\Participants;

use App\Models\Participant;
use App\Models\Program;
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

            // Cargar relaciones - usar programCourses en lugar de program
            $participant->load([
                'courses',
                'courses.institution',
                'courses.programCourses.program',
                'emergencyContacts'
            ]);

            // Ya no se usa participant_program, ahora los datos están en el pivot participant_course
            $participantPrograms = collect();

            // Obtener datos de programas del participante con precios calculados
            $participantProgramsData = $this->getParticipantProgramsData($participant, $participantPrograms);

            return [
                'participant' => $participant,
                'participantPrograms' => $participantProgramsData,
                'participantProgramsWithDiscounts' => collect(), // Ya no se usa participant_program
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
        // Obtener los cursos del participante con sus programas
        return $participant->courses()
            ->with(['institution', 'programCourses.program'])
            ->get()
            ->flatMap(function ($course) use ($participant) {
                return $course->programCourses->map(function ($programCourse) use ($course, $participant) {
                    $program = $programCourse->program;

                    // Calcular precio del participante para este program_course
                    // 1. Obtener el precio base del pivot participant_course
                    $pivot = $course->participants()->where('participant_id', $participant->id)->first()?->pivot;
                    $basePrice = $pivot?->individual_price ?? $programCourse->trip_price ?? 0;

                    // 2. Obtener ajustes del pivot
                    $adjustments = $pivot?->price_adjustments ?? 0;

                    // 3. Por ahora no hay descuentos (el sistema antiguo participant_program ya no se usa)
                    $discounts = 0;

                    // 4. Calcular precio final
                    $finalPrice = max(0, $basePrice + $adjustments - $discounts);

                    $priceData = [
                        'base_price' => $basePrice,
                        'adjustments' => $adjustments,
                        'discounts' => $discounts,
                        'final_price' => $finalPrice
                    ];

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

                    // Usar los datos del program_course (que tiene trip_price, departure_date, name específicos)
                    $array = $programCourse->toArray();
                    // Agregar la relación program (plantilla) para acceder a destination, images, etc.
                    $array['program'] = $program->toArray();
                    // Agregar datos del participante calculados
                    $array['participant_amount'] = $priceData['base_price']; // precio base por participante
                    $array['participant_adjustments'] = $priceData['adjustments']; // ajuste del pivote
                    $array['participant_total_due'] = $priceData['final_price']; // total a pagar (base + ajuste - descuentos)
                    $array['paidAmount'] = $paidAmount;
                    $array['participant_balance'] = $balance;
                    $array['paymentPercentage'] = $paymentPercentage;
                    $array['total_installments'] = $totalInstallments;
                    $array['paid_installments'] = $paidInstallments;
                    $array['installments_summary'] = $installmentsSummary;
                    $array['course'] = [
                        'id' => $course->id,
                        'institution' => $course->institution,
                        'education_level' => $course->education_level,
                        'grade' => $course->grade,
                        'year' => $course->year,
                    ];

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
            });
    }
}
