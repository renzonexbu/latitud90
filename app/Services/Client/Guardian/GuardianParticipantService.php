<?php

namespace App\Services\Client\Guardian;

use App\Models\EmergencyContact;
use App\Models\GuardianUser;
use App\Models\Participant;
use App\Models\ParticipantProgram;
use App\Models\Payment;
use App\Models\ProgramCourse;
use App\Models\ProgramSubscription;
use App\Models\InstallmentPlan;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Facades\DB;

class GuardianParticipantService
{
    /**
     * Obtener participantes vinculados al guardian
     */
    public function getParticipantsForGuardian(GuardianUser $user): array
    {
        // Obtener participantes desde la nueva tabla pivote
        $participants = $user->participants()
            ->with('documentType')
            ->get();

        // Transformar a estructura compatible con las vistas
        return $participants->map(function ($participant) {
            return [
                'id' => $participant->pivot->id ?? $participant->id,
                'is_primary' => true,
                'can_pay' => $participant->pivot->can_pay ?? true,
                'can_view_documents' => true,
                'can_view_itinerary' => true,
                'emergency_contact' => [
                    'id' => $participant->id,
                    'name' => $participant->full_name,
                    'email' => $participant->email,
                    'phone' => $participant->phone,
                    'relationship' => 'Apoderado',
                    'participant' => [
                        'id' => $participant->id,
                        'name' => $participant->full_name,
                        'document' => $participant->document_number,
                        'document_type' => $participant->documentType ? $participant->documentType->name : 'N/A',
                        'email' => $participant->email,
                        'phone' => $participant->code_phone && $participant->phone
                            ? '+' . $participant->code_phone . ' ' . $participant->phone
                            : $participant->phone,
                        'birth_date' => $participant->birth_date,
                    ]
                ]
            ];
        })->toArray();
    }

    /**
     * @deprecated Usar getParticipantsForGuardian en su lugar
     */
    public function getParticipantsByEmail(string $email): array
    {
        // Mantener compatibilidad: buscar por email en emergency_contact como fallback
        $emergencyContacts = EmergencyContact::where('email', $email)
            ->with('participant.documentType')
            ->get();

        return $emergencyContacts->map(function ($contact) {
            return [
                'id' => $contact->id,
                'is_primary' => true,
                'can_pay' => true,
                'can_view_documents' => true,
                'can_view_itinerary' => true,
                'emergency_contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'relationship' => $contact->relationship,
                    'participant' => $contact->participant ? [
                        'id' => $contact->participant->id,
                        'name' => $contact->participant->full_name,
                        'document' => $contact->participant->document_number,
                        'document_type' => $contact->participant->documentType ? $contact->participant->documentType->name : 'N/A',
                        'email' => $contact->participant->email,
                        'phone' => $contact->participant->code_phone && $contact->participant->phone
                            ? '+' . $contact->participant->code_phone . ' ' . $contact->participant->phone
                            : $contact->participant->phone,
                        'birth_date' => $contact->participant->birth_date,
                    ] : null
                ]
            ];
        })->toArray();
    }

    /**
     * Preparar datos del usuario con participantes para el dashboard
     */
    public function prepareUserDataForDashboard(GuardianUser $user): array
    {
        // Usar la nueva relacion directa
        $participants = $this->getParticipantsForGuardian($user);

        $userData = $user->toArray();
        $userData['guardian_links'] = $participants;

        return $userData;
    }

    /**
     * Obtener solo la lista de participantes
     */
    public function getParticipantsList(GuardianUser $user): array
    {
        return $this->getParticipantsForGuardian($user);
    }

    /**
     * Verificar si el guardian tiene acceso a un participante
     */
    public function guardianHasAccessToParticipant(GuardianUser $user, int $participantId): bool
    {
        // Verificar en la nueva tabla pivote
        return $user->participants()->where('participants.id', $participantId)->exists();
    }

    /**
     * Obtener los programas de un participante
     */
    public function getParticipantPrograms(Participant $participant): array
    {
        // Obtener los cursos del participante con sus program_courses
        $courses = $participant->courses()
            ->with(['programCourses.program'])
            ->get();

        $programs = [];

        foreach ($courses as $course) {
            // Para cada curso, obtener los program_courses (planes específicos)
            foreach ($course->programCourses as $programCourse) {
                if (!$programCourse->active) {
                    continue; // Skip inactive program courses
                }

                $program = $programCourse->program; // La plantilla del programa

                // Obtener el pivot del participante con este curso (para status y enrollment_code)
                $pivot = $course->participants()->where('participant_id', $participant->id)->first()?->pivot;

                // Usar ParticipantPriceHelper para calcular el precio (consistencia con otras vistas)
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
                $basePrice = (int) round($priceData['base_price']);
                $discountAmount = (int) round($priceData['discounts']);
                $finalPrice = (int) round($priceData['final_price']);

                // Obtener primera imagen del programa si existe
                $images = $program->images;
                $firstImage = !empty($images) ? $images[0]['url'] : null;

                // Buscar suscripción ACTIVA primero (priorizar sobre SUSCRIBIENDO, FALLIDA, etc.)
                $subscription = ProgramSubscription::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse->id)
                    ->whereIn('status', ['ACTIVA', 'activa', 'active', 'ACTIVE'])
                    ->first();

                // Si no hay activa, buscar cualquier otra para mostrar historial
                if (!$subscription) {
                    $subscription = ProgramSubscription::where('participant_id', $participant->id)
                        ->where('program_id', $programCourse->id)
                        ->orderByRaw("CASE
                            WHEN status IN ('ACTIVA', 'activa', 'active', 'ACTIVE') THEN 1
                            WHEN status IN ('FINALIZADA', 'finalizada') THEN 2
                            WHEN status IN ('CANCELADA', 'cancelada', 'cancelled', 'canceled') THEN 3
                            ELSE 4 END")
                        ->first();
                }

                // Obtener información de pagos - buscar por program_subscription_id primero
                $installmentPlan = null;
                if ($subscription) {
                    $installmentPlan = InstallmentPlan::where('program_subscription_id', $subscription->id)->first();
                }
                // Fallback para datos antiguos
                if (!$installmentPlan) {
                    $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                        ->where('program_id', $programCourse->id)
                        ->first();
                }

                // Determinar tipo de pago y estado
                $paymentType = 'none'; // none, subscription, full_payment
                $paymentStatus = null;
                $paidInstallments = 0;
                $totalInstallments = 0;

                if ($subscription) {
                    $paymentType = 'subscription';
                    $paymentStatus = $subscription->status;

                    // Contar cuotas pagadas
                    if ($installmentPlan) {
                        $totalInstallments = $installmentPlan->installments()->count();
                        $paidInstallments = $installmentPlan->installments()->where('status', 'paid')->count();
                    }
                } elseif ($installmentPlan) {
                    // Verificar si tiene un plan de cuotas (puede ser pago completo con cuotas)
                    $totalInstallments = $installmentPlan->installments()->count();
                    $paidInstallments = $installmentPlan->installments()->where('status', 'paid')->count();

                    if ($totalInstallments === 1 && $paidInstallments === 1) {
                        $paymentType = 'full_payment';
                        $paymentStatus = 'paid';
                    } elseif ($totalInstallments > 0) {
                        $paymentType = 'installments';
                        $paymentStatus = $paidInstallments === $totalInstallments ? 'completed' : 'in_progress';
                    }
                }

                $programs[] = [
                    'id' => $programCourse->id, // ID del program_course específico
                    'name' => $programCourse->name, // Nombre del plan específico
                    'description' => $program->trip_description,
                    'start_date' => $programCourse->departure_date, // Del plan específico
                    'departure_date' => $programCourse->departure_date, // Del plan específico
                    'end_date' => null,
                    'location' => $program->destination,
                    'price' => $finalPrice,
                    'base_price' => $basePrice,
                    'discount_amount' => $discountAmount,
                    'status' => $pivot?->status ?? 'active',
                    'enrollment_code' => $pivot?->enrollment_code,
                    'image' => $firstImage,
                    // Información de pago
                    'payment_type' => $paymentType,
                    'payment_status' => $paymentStatus,
                    'paid_installments' => $paidInstallments,
                    'total_installments' => $totalInstallments,
                    'has_subscription' => $subscription !== null,
                    'subscription_cancelled' => $subscription ? in_array(strtolower($subscription->status), ['cancelada', 'cancelled', 'canceled']) : false,
                ];
            }
        }

        return $programs;
    }

    /**
     * Obtener el detalle de un programa con sus mensualidades
     */
    public function getProgramDetailWithInstallments(Participant $participant, ProgramCourse $programCourse): ?array
    {
        // Verificar que el participante está inscrito en este programa
        $isEnrolled = $programCourse->course->participants()
            ->where('participant_id', $participant->id)
            ->exists();

        if (!$isEnrolled) {
            return null;
        }

        $program = $programCourse->program;

        // Usar ParticipantPriceHelper para calcular el precio (consistencia con otras vistas)
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
        $basePrice = (int) round($priceData['base_price']);
        $adjustments = (int) round($priceData['adjustments']);
        $discountAmount = (int) round($priceData['discounts']);
        $finalPrice = (int) round($priceData['final_price']);

        // Buscar suscripción ACTIVA primero (priorizar sobre SUSCRIBIENDO, FALLIDA, etc.)
        $subscription = ProgramSubscription::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->whereIn('status', ['ACTIVA', 'activa', 'active', 'ACTIVE'])
            ->first();

        // Si no hay activa, buscar cualquier otra para mostrar historial
        if (!$subscription) {
            $subscription = ProgramSubscription::where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id)
                ->orderByRaw("CASE
                    WHEN status IN ('ACTIVA', 'activa', 'active', 'ACTIVE') THEN 1
                    WHEN status IN ('FINALIZADA', 'finalizada') THEN 2
                    WHEN status IN ('CANCELADA', 'cancelada', 'cancelled', 'canceled') THEN 3
                    ELSE 4 END")
                ->first();
        }

        // Obtener el plan de cuotas - buscar por program_subscription_id primero
        $installmentPlan = null;
        if ($subscription) {
            $installmentPlan = InstallmentPlan::where('program_subscription_id', $subscription->id)
                ->with(['installments' => function($query) {
                    $query->orderBy('installment_number');
                }])
                ->first();
        }
        // Fallback para datos antiguos
        if (!$installmentPlan) {
            $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                ->where('program_id', $programCourse->id)
                ->with(['installments' => function($query) {
                    $query->orderBy('installment_number');
                }])
                ->first();
        }

        $installments = [];
        $totalInstallments = 0;
        $paidInstallments = 0;

        if ($installmentPlan) {
            $totalInstallments = $installmentPlan->installments->count();

            $installments = $installmentPlan->installments->map(function($installment) use (&$paidInstallments) {
                $isPaid = $installment->status === 'paid';

                if ($isPaid) {
                    $paidInstallments++;
                }

                return [
                    'id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'amount' => $installment->amount,
                    'due_date' => $installment->due_date,
                    'status' => $installment->status,
                    'is_paid' => $installment->is_paid,
                    'paid_at' => $installment->paid_at,
                    'virtualpos_charge_id' => $installment->virtualpos_charge_id,
                ];
            })->toArray();
        }

        // Calcular monto pagado (mismo cálculo que GetParticipantsService y SubscriptionController)
        // 1. Pagos normales desde payments
        $normalPayments = Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $programCourse->id);
            })
            ->whereIn('status', ['completed', 'approved'])
            ->sum('amount');

        // 2. Cuotas de suscripción pagadas (installments)
        $subscriptionPayments = DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.participant_id', $participant->id)
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        $paidAmount = (int) round((float) $normalPayments + (float) $subscriptionPayments);

        // Obtener primera imagen del programa si existe
        $images = $program->images;
        $firstImage = !empty($images) ? $images[0]['url'] : null;

        // Determinar tipo de pago
        $paymentType = 'none';
        if ($subscription) {
            $paymentType = 'subscription';
        } elseif ($installmentPlan && $totalInstallments === 1 && $paidInstallments === 1) {
            $paymentType = 'full_payment';
        } elseif ($installmentPlan && $totalInstallments > 0) {
            $paymentType = 'installments';
        }

        // Verificar si la suscripción está cancelada
        $subscriptionCancelled = $subscription && in_array(
            strtolower($subscription->status),
            ['cancelada', 'cancelled', 'canceled']
        );

        return [
            'id' => $programCourse->id,
            'name' => $programCourse->name,
            'description' => $program->trip_description,
            'destination' => $program->destination,
            'departure_date' => $programCourse->departure_date,
            'price' => $finalPrice,
            'base_price' => $basePrice,
            'adjustments' => $adjustments,
            'discount_amount' => $discountAmount,
            'image' => $firstImage,
            'images' => $images,
            // Tipo de pago
            'payment_type' => $paymentType,
            'subscription_cancelled' => $subscriptionCancelled,
            // Información de suscripción
            'has_subscription' => $subscription !== null,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'virtualpos_subscription_id' => $subscription->virtualpos_subscription_id,
                'payment_method' => $subscription->payment_method,
                'created_at' => $subscription->created_at->toDateString(),
            ] : null,
            // Información de cuotas
            'installments' => $installments,
            'total_installments' => $totalInstallments,
            'paid_installments' => $paidInstallments,
            'paid_amount' => $paidAmount,
            'pending_amount' => max(0, $finalPrice - $paidAmount),
            'payment_percentage' => $finalPrice > 0 ? round(($paidAmount / $finalPrice) * 100, 2) : 0,
        ];
    }
}
