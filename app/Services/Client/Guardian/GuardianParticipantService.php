<?php

namespace App\Services\Client\Guardian;

use App\Models\EmergencyContact;
use App\Models\GuardianUser;
use App\Models\Participant;
use App\Models\ProgramCourse;
use App\Models\ProgramSubscription;
use App\Models\InstallmentPlan;

class GuardianParticipantService
{
    /**
     * Obtener participantes asociados al email del guardian
     */
    public function getParticipantsByEmail(string $email): array
    {
        // Buscar contactos de emergencia que tengan el email del guardian
        $emergencyContacts = EmergencyContact::where('email', $email)
            ->with('participant.documentType')
            ->get();

        // Transformar a estructura compatible con las vistas
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
        $participants = $this->getParticipantsByEmail($user->email);

        $userData = $user->toArray();
        $userData['guardian_links'] = $participants;

        return $userData;
    }

    /**
     * Obtener solo la lista de participantes
     */
    public function getParticipantsList(GuardianUser $user): array
    {
        return $this->getParticipantsByEmail($user->email);
    }

    /**
     * Verificar si el guardian tiene acceso a un participante
     */
    public function guardianHasAccessToParticipant(GuardianUser $user, int $participantId): bool
    {
        return EmergencyContact::where('email', $user->email)
            ->where('participant_id', $participantId)
            ->exists();
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

                // Obtener el pivot del participante con este curso
                $pivot = $course->participants()->where('participant_id', $participant->id)->first()?->pivot;

                // Obtener primera imagen del programa si existe
                $images = $program->images;
                $firstImage = !empty($images) ? $images[0]['url'] : null;

                $programs[] = [
                    'id' => $programCourse->id, // ID del program_course específico
                    'name' => $programCourse->name, // Nombre del plan específico
                    'description' => $program->trip_description,
                    'start_date' => $programCourse->departure_date, // Del plan específico
                    'departure_date' => $programCourse->departure_date, // Del plan específico
                    'end_date' => null,
                    'location' => $program->destination,
                    'price' => $pivot?->individual_price ?? $programCourse->trip_price,
                    'status' => $pivot?->status ?? 'active',
                    'enrollment_code' => $pivot?->enrollment_code,
                    'image' => $firstImage,
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

        // Obtener el precio desde el pivote
        $pivot = $programCourse->course->participants()
            ->where('participant_id', $participant->id)
            ->first()?->pivot;

        $basePrice = $pivot?->individual_price ?? $programCourse->trip_price ?? 0;
        $adjustments = $pivot?->price_adjustments ?? 0;
        $finalPrice = max(0, $basePrice + $adjustments);

        // Obtener la suscripción activa si existe
        $subscription = ProgramSubscription::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->whereIn('status', ['ACTIVA', 'SUSCRIBIENDO'])
            ->first();

        // Obtener el plan de cuotas
        $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
            ->where('program_id', $programCourse->id)
            ->with(['installments' => function($query) {
                $query->orderBy('installment_number');
            }])
            ->first();

        $installments = [];
        $totalInstallments = 0;
        $paidInstallments = 0;
        $paidAmount = 0;

        if ($installmentPlan) {
            $totalInstallments = $installmentPlan->installments->count();

            $installments = $installmentPlan->installments->map(function($installment) use (&$paidInstallments, &$paidAmount) {
                $isPaid = $installment->status === 'paid' && $installment->is_paid;

                if ($isPaid) {
                    $paidInstallments++;
                    $paidAmount += (float) $installment->amount;
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

        // Obtener primera imagen del programa si existe
        $images = $program->images;
        $firstImage = !empty($images) ? $images[0]['url'] : null;

        return [
            'id' => $programCourse->id,
            'name' => $programCourse->name,
            'description' => $program->trip_description,
            'destination' => $program->destination,
            'departure_date' => $programCourse->departure_date,
            'price' => $finalPrice,
            'base_price' => $basePrice,
            'adjustments' => $adjustments,
            'image' => $firstImage,
            'images' => $images,
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
            'paid_amount' => round($paidAmount, 2),
            'pending_amount' => round($finalPrice - $paidAmount, 2),
            'payment_percentage' => $finalPrice > 0 ? round(($paidAmount / $finalPrice) * 100, 2) : 0,
        ];
    }
}
