<?php

namespace App\Services\Client;

use App\Models\Participant;
use App\Models\Course;
use App\Models\Program;

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

            if (!$isEnrolled) {
                $availablePrograms[] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'description' => $program->trip_description,
                    'institution' => $program->course->institution->name ?? 'N/A',
                    'base_price' => $program->trip_price,
                    'duration_days' => $this->calculateDurationDays($program->departure_date),
                    'start_date' => $program->departure_date,
                    'end_date' => $program->departure_date, // Usar la misma fecha por ahora
                    'destination' => $program->destination,
                    'capacity' => 50, // Valor por defecto
                    'available_spots' => 50 - $enrolledCount,
                    'image_url' => $program->images[0] ?? '/images/default-program.jpg',
                    'status' => 'available'
                ];
            } else {
                // Si ya está inscrito, mostrar información del estado
                $enrollment = $participant->courses()
                    ->where('course_id', $program->course_id)
                    ->first();
                
                $availablePrograms[] = [
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
                    'status' => $enrollment->pivot->status ?? 'enrolled',
                    'enrollment_date' => $enrollment->pivot->created_at ?? null
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
}
