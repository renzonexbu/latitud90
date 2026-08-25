<?php

namespace App\Services\Client\Programs;

use App\Models\Participant;
use App\Models\ParticipantProgram;
use App\Models\Course;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\InstallmentPlan;
use App\Models\Order;
use App\Models\ProgramSubscription;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    use SystemLogging;
    public function getParticipantByDocument(string $document, string $documentType): ?Participant
    {
        // Limpiar el documento de puntos y guiones
        $cleanDocument = $this->cleanDocument($document);
        
        // No filtrar por participants.is_active — la validación real es
        // participant_program.is_active por programa (getAvailablePrograms ya filtra)
        $participant = Participant::join('document', 'participants.document_type', '=', 'document.id')
            ->where('participants.document_number', $cleanDocument)
            ->where('document.name', $documentType)
            ->select('participants.*')
            ->with(['courses', 'emergencyContacts'])
            ->first();
            
        return $participant;
    }

    public function getParticipantByRut(string $rut): ?Participant
    {
        // Mantener compatibilidad con el método anterior
        return $this->getParticipantByDocument($rut, 'RUT');
    }

    public function getAvailablePrograms(Participant $participant): array
    {
        // Obtener todos los enrollment_codes cancelados para este participante
        $cancelledEnrollmentCodes = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
            ->where('status', 'cancelled')
            ->pluck('enrollment_code')
            ->toArray();

        // Obtener los cursos del participante con sus programCourses (planes específicos)
        $courses = $participant->courses()
            ->with(['institution', 'programCourses.program'])
            ->get();

        $availablePrograms = [];

        foreach ($courses as $course) {
            // Para cada curso, obtener los program_courses (planes específicos)
            foreach ($course->programCourses as $programCourse) {
                if (!$programCourse->active) {
                    continue; // Skip inactive program courses
                }

                $program = $programCourse->program; // La plantilla del programa

                // Generar el enrollment_code para este programa
                // Extraer solo la parte numérica del código (antes del guión)
                $programCodePart = explode('-', $programCourse->code)[0];
                $enrollmentCode = $participant->document_number . '-' . $programCodePart;

                // Verificar si este enrollment_code específico está cancelado
                if (in_array($enrollmentCode, $cancelledEnrollmentCodes)) {
                    continue; // Skip este programa cancelado específicamente
                }

                // Obtener el pivot del participante con este curso
                $pivot = $course->participants()->where('participant_id', $participant->id)->first()?->pivot;

                // Calcular precio del participante para este program_course
                $basePrice = $pivot?->individual_price ?? $programCourse->trip_price ?? 0;
                $adjustments = $pivot?->price_adjustments ?? 0;

                // Obtener descuentos desde participant_program_discounts
                $discounts = 0;
                $participantProgram = ParticipantProgram::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id)
                    ->with('discounts')
                    ->first();

                // Si el participante está dado de baja de este programa específico, no mostrarlo
                if ($participantProgram && $participantProgram->is_active === false) {
                    continue;
                }

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
                            $discounts += ($basePrice * $discount->percent / 100);
                        } elseif ($discount->amount) {
                            $discounts += $discount->amount;
                        }
                    }
                }

                $finalPrice = max(0, $basePrice + $adjustments - $discounts);

                // Calcular cuotas y monto pagado
                $totalInstallments = 0;
                $paidInstallments = 0;
                $paidAmount = 0;
                $installmentsSummary = null;

                // IMPORTANTE: Calcular monto pagado directamente desde la tabla payments
                // Esto asegura que funcione tanto para suscripciones como para pagos totales
                // NOTA: La tabla orders guarda program_id como el ID del ProgramCourse, no del Program template
                // CT (Crédito Temporal) SÍ cuenta como abono: la contabilidad de Latitud90
                // lo trata como pago hecho (Precio - CT - Liberado = Saldo).
                $paidAmountFromPayments = Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                        $q->where('participant_id', $participant->id)
                          ->where('program_id', $programCourse->id); // Usar programCourse->id, no program->id
                    })
                    ->whereIn('status', ['completed', 'approved'])
                    ->sum('amount');

                // Buscar planes de cuotas del participante para este programa
                // NOTA: InstallmentPlan también debe usar programCourse->id
                // IMPORTANTE: Solo mostrar cuotas de suscripciones ACTIVAS o de órdenes sin suscripción
                $installmentPlans = InstallmentPlan::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereHas('order', function($q) {
                        // Solo incluir si:
                        // 1. La orden no tiene suscripción (pago total/manual), O
                        // 2. La orden tiene suscripción ACTIVA
                        $q->where(function($subQ) {
                            $subQ->whereNull('subscription_id')
                                ->orWhereHas('subscription', function($subSubQ) {
                                    $subSubQ->where('status', 'ACTIVA');
                                });
                        });
                    })
                    ->with(['installments' => function($q) {
                        // Excluir cuotas canceladas
                        $q->where('status', '!=', 'cancelled');
                    }])
                    ->get();

                foreach ($installmentPlans as $plan) {
                    $totalInstallments = $plan->installments->count();

                    foreach ($plan->installments as $installment) {
                        // Contar cuotas pagadas y sumar sus montos
                        if ($installment->status === 'paid' && $installment->is_paid) {
                            $paidInstallments++;
                            $paidAmount += (float) $installment->amount;
                        }
                    }
                }

                $paidAmount = round($paidAmount, 2);
                $participantBalance = max(round($finalPrice - $paidAmount, 2), 0);
                $paymentPercentage = $finalPrice > 0 ? round(($paidAmount / $finalPrice) * 100, 2) : 0;

                // Si no hay planes de cuotas, buscar en orders como fallback
                if ($totalInstallments == 0) {
                    // NOTA: orders.program_id es el ID del ProgramCourse
                    $orders = Order::where('participant_id', $participant->id)
                        ->where('program_id', $programCourse->id)
                        ->where('notes', '!=', 'Orden creada desde reembolso')
                        // Excluir órdenes de suscripción ya que no representan pagos inmediatos
                        ->where('order_number', 'NOT LIKE', 'SUB-%')
                        ->with(['orderDetails.paymentOption'])
                        ->get();

                    foreach ($orders as $order) {
                        // Si la orden tiene total_installments = 0, es un sistema sin cuotas (pagos presenciales)
                        // No contar los OrderDetails como cuotas, solo como pagos para el porcentaje
                        if ($order->total_installments == 0) {
                            continue; // No contar cuotas para órdenes presenciales
                        }

                        if ($order->orderDetails) {
                            $ecommerceOrderDetails = $order->orderDetails->filter(function($detail) {
                                if ($detail->paymentOption && $detail->paymentOption->mode === 'presential') {
                                    return false;
                                }
                                if ($detail->paymentOption && $detail->paymentOption->code === 'refund_credit_note') {
                                    return false;
                                }
                                return true;
                            });

                            $totalInstallments = $ecommerceOrderDetails->count();

                            foreach ($ecommerceOrderDetails as $detail) {
                                $isRefundDetail = Payment::where('order_detail_id', $detail->id)
                                    ->whereHas('paymentOption', function($q) {
                                        $q->where('code', 'refund_credit_note');
                                    })
                                    ->exists();

                                if ($detail->is_paid && !$isRefundDetail) {
                                    $paidInstallments++;
                                    // Usar amount en lugar de price (price puede estar vacío en pagos totales)
                                    $paidAmount += (float) ($detail->price ?: $detail->amount);
                                }
                            }
                        }
                    }

                    // Recalcular porcentaje y balance si se encontraron pagos en orders
                    if ($paidAmount > 0) {
                        $paidAmount = round($paidAmount, 2);
                        $participantBalance = max(round($finalPrice - $paidAmount, 2), 0);
                        $paymentPercentage = $finalPrice > 0 ? round(($paidAmount / $finalPrice) * 100, 2) : 0;
                    }
                }

                // IMPORTANTE: Si hay pagos registrados en la tabla payments, usar ese monto
                // Esto es la fuente de verdad y funciona para todos los tipos de pago
                // Usar exists() en vez de sum > 0 para cubrir el caso de reembolso total (sum = 0)
                $hasPayments = Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                        $q->where('participant_id', $participant->id)
                          ->where('program_id', $programCourse->id);
                    })
                    ->whereIn('status', ['completed', 'approved'])
                    ->exists();

                if ($hasPayments) {
                    $paidAmount = max(round($paidAmountFromPayments, 2), 0);
                    $participantBalance = max(round($finalPrice - $paidAmount, 2), 0);
                    $paymentPercentage = $finalPrice > 0 ? round(($paidAmount / $finalPrice) * 100, 2) : 0;
                }

                // Verificar si existe una suscripción para este participante y programa
                $subscription = ProgramSubscription::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id)
                    ->first();

                $subscriptionCancelled = $subscription && in_array(
                    strtolower($subscription->status),
                    ['cancelada', 'cancelled', 'canceled']
                );

                $hasActiveSubscription = $subscription && strtolower($subscription->status) === 'activa';

                // Crear resumen de cuotas SOLO si hay suscripción ACTIVA
                // Si no hay suscripción o está cancelada, se muestra el porcentaje de pago
                if ($hasActiveSubscription && $totalInstallments > 0) {
                    $installmentsSummary = "{$paidInstallments}/{$totalInstallments}";
                }

                $availablePrograms[] = [
                    'id' => $programCourse->id, // ID del program_course específico
                    'name' => $programCourse->name, // Nombre del plan específico
                    'code' => $programCourse->code, // Código del plan
                    'trip_description' => $program->trip_description, // De la plantilla
                    'destination' => $program->destination, // De la plantilla
                    'departure_date' => $programCourse->departure_date, // Del plan específico
                    'trip_price' => $programCourse->trip_price, // Del plan específico
                    'course' => [
                        'id' => $course->id,
                        'institution' => $course->institution,
                        'education_level' => $course->education_level,
                        'grade' => $course->grade,
                        'year' => $course->year,
                    ],
                    'program' => [ // Datos de la plantilla
                        'id' => $program->id,
                        'name' => $program->name,
                        'destination' => $program->destination,
                        'images' => $program->images, // Accessor de la plantilla
                    ],
                    'images' => $program->images, // Accessor de la plantilla
                    'paymentPercentage' => $paymentPercentage,
                    'paidAmount' => $paidAmount,
                    'totalAmount' => $finalPrice,
                    'participant_total_due' => $finalPrice,
                    'participant_balance' => $participantBalance,
                    'participant_amount' => $basePrice,
                    'participant_adjustments' => $adjustments,
                    'participant_discounts' => $discounts,
                    'total_installments' => $totalInstallments,
                    'paid_installments' => $paidInstallments,
                    'installments_summary' => $installmentsSummary,
                    'status' => 'enrolled',
                    'enrollment_date' => $pivot?->created_at ?? null,
                    'enrollment_code' => $enrollmentCode,
                    'subscription_cancelled' => $subscriptionCancelled,
                    'has_subscription' => $subscription !== null,
                ];
            }
        }

        return $availablePrograms;
    }

    public function getProgramById(int $programId): ?array
    {
        // IMPORTANTE: $programId es en realidad un ProgramCourse ID
        $programCourse = \App\Models\ProgramCourse::with(['program.features', 'program.requirements', 'course.institution'])
            ->find($programId);

        if (!$programCourse) {
            return null;
        }

        $program = $programCourse->program;
        $course = $programCourse->course;

        // Contar participantes inscritos en este curso
        $enrolledCount = 0;
        if ($course) {
            $enrolledCount = $course->participants()->count();
        }

        return [
            'id' => $programCourse->id,
            'name' => $programCourse->name ?: $program->name,
            'description' => $program->trip_description,
            'institution' => $course->institution->name ?? 'N/A',
            'base_price' => $programCourse->trip_price,
            'duration_days' => $this->calculateDurationDays($programCourse->departure_date),
            'start_date' => $programCourse->departure_date,
            'end_date' => $programCourse->departure_date,
            'destination' => $program->destination,
            'capacity' => 50,
            'available_spots' => 50 - $enrolledCount,
            'image_url' => $program->images[0]['url'] ?? '/images/default-program.jpg',
            'itinerary' => $program->itinerary_description ?? '',
            'included_services' => $program->features->where('pivot.type', 'included')->pluck('name')->toArray(),
            'not_included_services' => $program->features->where('pivot.type', 'not_included')->pluck('name')->toArray(),
            'requirements' => $program->requirements->pluck('name')->toArray(),
            'important_notes' => $program->pillars ?? ''
        ];
    }

    private function calculateDurationDays($departureDate): int
    {
        // Por ahora retornamos un valor por defecto
        // En el futuro se puede calcular basado en el itinerario
        return 7;
    }
    
    private function cleanRut(string $rut): string
    {
        // Remover puntos y guiones del RUT
        return preg_replace('/[.-]/', '', $rut);
    }
    
    private function cleanDocument(string $document): string
    {
        // Remover puntos y guiones del documento
        return preg_replace('/[.-]/', '', $document);
    }
    
    private function translateStatus(string $status): string
    {
        $translations = [
            'pending_payment' => 'Pendiente de Pago',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            'enrolled' => 'Inscrito'
        ];
        
        return $translations[$status] ?? $status;
    }
}
