<?php

namespace App\Services\Client;

use App\Models\Participant;
use App\Models\Course;
use App\Models\Program;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    public function getParticipantByRut(string $rut): ?Participant
    {
        // Limpiar el RUT de puntos y guiones
        $cleanRut = $this->cleanRut($rut);
        
        // Buscar el participante en la base de datos
        $participant = Participant::where('document_number', $cleanRut)
            ->where('document_type', 'RUT')
            ->with(['courses', 'emergencyContacts'])
            ->first();
            
        return $participant;
    }

    public function getAvailablePrograms(Participant $participant): array
    {
        // Obtener todos los programas disponibles
        $programs = Program::where('active', true)
            ->with(['course.institution', 'features', 'requirements'])
            ->get();

        $availablePrograms = [];

        foreach ($programs as $program) {
            // Verificar si el participante ya está inscrito en este programa
            // Buscar a través de la relación course del programa
            $isEnrolled = $participant->courses()
                ->where('course_id', $program->course_id)
                ->exists();

            // Contar participantes inscritos en este programa
            $enrolledCount = 0;
            if ($program->course) {
                $enrolledCount = $program->course->participants()->count();
            }

            // Calcular montos pagados y adeudados por participante
            $paymentPercentage = 0;
            $paidAmount = 0;
            $totalAmount = $program->trip_price; // total de referencia si no hay inscripción
            $participantTotalAmount = $program->trip_price; // total a pagar por participante (base + ajuste)
            $participantBalance = $program->trip_price; // saldo remanente por defecto

            if (!$isEnrolled) {
                $availablePrograms[] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'trip_description' => $program->trip_description,
                    'destination' => $program->destination,
                    'departure_date' => $program->departure_date,
                    'trip_price' => $program->trip_price,
                    'course' => $program->course,
                    'images' => $program->images,
                    'paymentPercentage' => $paymentPercentage,
                    'paidAmount' => $paidAmount,
                    'totalAmount' => $participantTotalAmount,
                    'participant_total_due' => $participantTotalAmount, // mantener mismo nombre que Admin/Edit.vue
                    'participant_balance' => $participantBalance,
                    'status' => 'available'
                ];
            } else {
                // Si ya está inscrito, mostrar información del estado
                $enrollment = $participant->courses()
                    ->where('course_id', $program->course_id)
                    ->first();
                // Obtener enrollment_code real desde participant_program (programa-participante)
                $pp = DB::table('participant_program')
                    ->where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->first();
                $enrollmentCode = $pp->enrollment_code ?? null;
                
                // Usar el helper para calcular el precio final con descuentos
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
                $totalAmount = $priceData['final_price'];

                // Sumar pagos confirmados usando OrderDetails pagados (más fiable que estado del payment)
                $paidAmount = (float) OrderDetail::whereHas('order', function($q) use ($participant, $program) {
                        $q->where('participant_id', $participant->id)
                          ->where('program_id', $program->id);
                    })
                    ->where('is_paid', true)
                    ->sum('amount');

                $paidAmount = round($paidAmount, 2);
                $participantBalance = max(round($totalAmount - $paidAmount, 2), 0);
                $participantTotalAmount = $totalAmount;
                $paymentPercentage = $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0;

                $availablePrograms[] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'trip_description' => $program->trip_description,
                    'destination' => $program->destination,
                    'departure_date' => $program->departure_date,
                    'trip_price' => $program->trip_price,
                    'course' => $program->course,
                    'images' => $program->images,
                    'paymentPercentage' => $paymentPercentage,
                    'paidAmount' => $paidAmount,
                    'totalAmount' => $totalAmount,
                    'participant_total_due' => $participantTotalAmount, // mismo uso que Admin/Edit.vue
                    'participant_balance' => $participantBalance,
                    'participant_amount' => $priceData['base_price'],
                    'participant_adjustments' => $priceData['adjustments'],
                    'status' => $this->translateStatus($enrollment->pivot->status ?? 'enrolled'),
                    'enrollment_date' => $enrollment->pivot->created_at ?? null
                    ,
                    'enrollment_code' => $enrollmentCode
                ];
            }
        }

        return $availablePrograms;
    }

    public function getProgramById(int $programId): ?array
    {
        $program = Program::with(['course.institution', 'features', 'requirements'])
            ->find($programId);

        if (!$program) {
            return null;
        }

        // Contar participantes inscritos en este programa
        $enrolledCount = 0;
        if ($program->course) {
            $enrolledCount = $program->course->participants()->count();
        }

        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->trip_description,
            'institution' => $program->course->institution->name ?? 'N/A',
            'base_price' => $program->trip_price,
            'duration_days' => $this->calculateDurationDays($program->departure_date),
            'start_date' => $program->departure_date,
            'end_date' => $program->departure_date,
            'destination' => $program->destination,
            'capacity' => 50,
            'available_spots' => 50 - $enrolledCount,
            'image_url' => $program->images[0] ?? '/images/default-program.jpg',
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
