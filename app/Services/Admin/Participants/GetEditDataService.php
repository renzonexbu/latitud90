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

            // Obtener datos de programas del participante con precios calculados
            $participantProgramsData = $this->getParticipantProgramsData($participant);

            // Obtener participant_programs con descuentos para cada program_course
            $participantProgramsWithDiscounts = $this->getParticipantProgramsWithDiscounts($participant);

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
     * Obtener participant_programs con descuentos para cada program_course
     *
     * @param Participant $participant
     * @return \Illuminate\Support\Collection
     */
    private function getParticipantProgramsWithDiscounts(Participant $participant)
    {
        // Obtener todos los participant_program del participante con sus descuentos
        $participantPrograms = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
            ->with(['discounts'])
            ->get();

        return $participantPrograms->map(function ($pp) {
            return [
                'id' => $pp->id,
                'participant_id' => $pp->participant_id,
                'program_id' => $pp->program_id, // Este es el program_course_id
                'status' => $pp->status,
                'is_active' => $pp->is_active ?? true, // Estado activo/baja del programa
                'enrollment_code' => $pp->enrollment_code,
                'discounts' => $pp->discounts->map(function ($discount) {
                    return [
                        'id' => $discount->id,
                        'percent' => $discount->percent,
                        'amount' => $discount->amount,
                        'discount_type' => $discount->discount_type,
                        'comment' => $discount->comment,
                        'approved_by' => $discount->approved_by,
                    ];
                }),
                'installment_plan' => null, // Se puede agregar si es necesario
            ];
        });
    }

    /**
     * Obtener datos de programas del participante con precios calculados
     *
     * @param Participant $participant
     * @return \Illuminate\Support\Collection
     */
    private function getParticipantProgramsData(Participant $participant)
    {
        // Obtener todos los enrollment_codes cancelados para este participante
        $cancelledEnrollmentCodes = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
            ->where('status', 'cancelled')
            ->pluck('enrollment_code')
            ->toArray();

        // Obtener los cursos del participante con sus programas
        return $participant->courses()
            ->with(['institution', 'programCourses.program'])
            ->get()
            ->flatMap(function ($course) use ($participant, $cancelledEnrollmentCodes) {
                return $course->programCourses->map(function ($programCourse) use ($course, $participant, $cancelledEnrollmentCodes) {
                    $program = $programCourse->program;

                    // Generar el enrollment_code para este programa
                    // Extraer solo la parte numérica del código (antes del guión)
                    $programCodePart = explode('-', $programCourse->code)[0];
                    $enrollmentCode = $participant->document_number . '-' . $programCodePart;

                    // Verificar si este enrollment_code específico está cancelado (no filtrar, solo marcar)
                    $isCancelled = in_array($enrollmentCode, $cancelledEnrollmentCodes);

                    // Calcular precio del participante para este program_course
                    // 1. Obtener el precio base del pivot participant_course
                    $pivot = $course->participants()->where('participant_id', $participant->id)->first()?->pivot;
                    $basePrice = $pivot?->individual_price ?? $programCourse->trip_price ?? 0;

                    // 2. Obtener ajustes del pivot
                    $adjustments = $pivot?->price_adjustments ?? 0;

                    // 3. Obtener descuentos desde participant_program_discounts
                    $discounts = 0;
                    $participantProgram = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                        ->where('program_id', $programCourse->id)
                        ->with('discounts')
                        ->first();

                    if ($participantProgram && $participantProgram->discounts) {
                        foreach ($participantProgram->discounts as $discount) {
                            if ($discount->discount_type === 'released') {
                                // Liberado: si hay amount fijo se usa; sino aplica percent (default 100%).
                                if (!empty($discount->amount)) {
                                    $discounts += (float) $discount->amount;
                                } else {
                                    $discountPercent = $discount->percent ?? 100;
                                    $discounts += ($basePrice * $discountPercent / 100);
                                }
                            } elseif ($discount->percent) {
                                // Descuento porcentual
                                $discounts += ($basePrice * $discount->percent / 100);
                            } elseif ($discount->amount) {
                                // Descuento de monto fijo
                                $discounts += $discount->amount;
                            }
                        }
                    }

                    // 4. Calcular precio final
                    $finalPrice = max(0, $basePrice + $adjustments - $discounts);

                    $priceData = [
                        'base_price' => $basePrice,
                        'adjustments' => $adjustments,
                        'discounts' => $discounts,
                        'final_price' => $finalPrice
                    ];

                // Pagos aprobados/completados del participante para este programa
                // NOTA: orders.program_id guarda el ID del ProgramCourse, no del Program template
                // Calcular TODOS los pagos (incluidos aportes) para balance y porcentaje
                $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                    $q->where('participant_id', $participant->id)
                        ->where('program_id', $programCourse->id);
                })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');

                $paidAmount = round($paidAmount, 2);

                // Calcular aportes por separado solo para mostrar en columna APORTE
                $aportes = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                    $q->where('participant_id', $participant->id)
                        ->where('program_id', $programCourse->id);
                })
                    ->whereIn('status', ['approved', 'completed'])
                    ->whereHas('paymentOption', function($q) {
                        $q->where('report_code', 'AP');
                    })
                    ->sum('amount');

                $aportes = round($aportes, 2);

                // Calcular becas (descuentos tipo scholarship)
                $becas = 0;
                if ($participantProgram && $participantProgram->discounts) {
                    foreach ($participantProgram->discounts as $discount) {
                        if ($discount->discount_type === 'scholarship') {
                            if ($discount->amount) {
                                $becas += $discount->amount;
                            } elseif ($discount->percent) {
                                $becas += ($basePrice * $discount->percent / 100);
                            }
                        }
                    }
                }
                $becas = round($becas, 2);

                // Balance = Precio final - Total pagado - Becas
                // IMPORTANTE: paidAmount ya incluye todos los pagos (incluidos aportes)
                $balance = max(round($priceData['final_price'] - $paidAmount - $becas, 2), 0);

                // Porcentaje de pago basado en el total pagado (ya incluye aportes)
                $paymentPercentage = ($priceData['final_price'] > 0)
                    ? round(($paidAmount / $priceData['final_price']) * 100, 0)
                    : 0;

                // Calcular información de cuotas
                $totalInstallments = 0;
                $paidInstallments = 0;
                $installmentsSummary = null;
                $installmentPlan = null;

                // Buscar planes de cuotas del participante para este programa
                // NOTA: InstallmentPlan también debe usar programCourse->id
                $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id)
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
                    // NOTA: orders.program_id es el ID del ProgramCourse
                    $orders = \App\Models\Order::where('participant_id', $participant->id)
                        ->where('program_id', $programCourse->id)
                        ->with(['orderDetails'])
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

                // Verificar si tiene suscripción activa (necesario para mostrar cuotas)
                $activeSubscription = \App\Models\ProgramSubscription::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id)
                    ->where('status', 'ACTIVA')
                    ->first();

                // Crear resumen de cuotas SOLO si hay suscripción activa o plan de cuotas activo
                // No mostrar cuotas para pagos presenciales o descuentos
                if ($totalInstallments > 0 && ($activeSubscription !== null || $installmentPlan !== null)) {
                    $installmentsSummary = "{$paidInstallments}/{$totalInstallments}";
                }

                    // Obtener el status y is_active desde participant_program usando el enrollment_code específico
                    $participantProgramRecord = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                        ->where('enrollment_code', $enrollmentCode)
                        ->first();
                    $participantProgramStatus = $participantProgramRecord?->status ?? 'pending_payment';
                    $participantProgramIsActive = $participantProgramRecord?->is_active ?? true;

                    // Usar los datos del program_course (que tiene trip_price, departure_date, name específicos)
                    $array = $programCourse->toArray();
                    // Agregar la relación program (plantilla) para acceder a destination, images, etc.
                    $array['program'] = $program->toArray();
                    // Agregar datos del participante calculados
                    $array['participant_amount'] = $priceData['base_price']; // precio base por participante
                    $array['participant_adjustments'] = $priceData['adjustments']; // ajuste del pivote
                    $array['participant_total_due'] = $priceData['final_price']; // total a pagar (base + ajuste - descuentos)
                    $array['paidAmount'] = $paidAmount; // Total pagado (incluye todos los pagos + aportes)
                    $array['aportes'] = $aportes; // Aportes (pagos con report_code='AP') - solo para mostrar
                    $array['becas'] = $becas; // Becas (descuentos tipo scholarship)
                    $array['participant_balance'] = $balance;
                    $array['paymentPercentage'] = $paymentPercentage;
                    $array['total_installments'] = $totalInstallments;
                    $array['paid_installments'] = $paidInstallments;
                    $array['installments_summary'] = $installmentsSummary;
                    $array['participant_program_status'] = $participantProgramStatus; // Status del participant_program
                    $array['participant_program_is_active'] = $participantProgramIsActive; // Estado activo/baja del programa
                    $array['is_cancelled'] = $isCancelled; // Bandera para saber si está cancelado

                    // Usar la variable $activeSubscription ya calculada arriba
                    $array['has_active_subscription'] = $activeSubscription !== null;

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
            })
            ->values(); // Reindexar para asegurar que se serialice como array
    }
}
