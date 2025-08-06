<?php

namespace App\Services\Client;

use App\Models\Program;
use App\Models\Participant;
use App\Models\Institution;
use App\Models\Course;
use App\Models\Feature;
use App\Models\Requirement;

class ProgramDetailService
{
    public function getProgramDetails($programId, $participantId)
    {
        $program = Program::with([
            'features',
            'requirements'
        ])->find($programId);

        if (!$program) {
            return null;
        }

        // Obtener el participante
        $participant = Participant::find($participantId);

        // Verificar si el participante ya está inscrito en este programa
        // Por ahora, simplificamos esta verificación
        $isEnrolled = false; // TODO: Implementar lógica de verificación de inscripción

        // Obtener información completa del programa
        $programData = [
            'id' => $program->id,
            'name' => $program->name,
            'destination' => $program->destination,
            'trip_description' => $program->trip_description,
            'trip_price' => $program->trip_price,
            'departure_date' => $program->departure_date,
            'final_payment_date' => $program->final_payment_date,
            'seller_name' => $program->seller_name,
            'active' => $program->active,
            'is_enrolled' => $isEnrolled,
            
            // Archivos PDF
            'itinerary_file' => $program->itinerary_file_url,
            'travel_assistance_coverage' => $program->travel_assistance_coverage_url,
            'equipment_list' => $program->equipment_list_url,
            
            // Información adicional
            'itinerary_description' => $program->itinerary_description,
            'pillars' => $program->pillars,
            'images' => $program->images,
            'images_folder' => $program->images_folder,
            
            // Información de pago
            'payment_mode' => $program->paymentMode ? [
                'id' => $program->paymentMode->id,
                'name' => $program->paymentMode->name,
            ] : null,
            'payment_method' => $program->paymentMethod ? [
                'id' => $program->paymentMethod->id,
                'name' => $program->paymentMethod->name,
            ] : null,
            'max_installments' => $program->max_installments,
            'discount_type' => $program->discount_type,
            'discount_value' => $program->discount_value,
            

            

            
            // Características
            'features' => $program->features->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'description' => $feature->description ?? '',
                    'icon' => $feature->icon ?? '✓',
                ];
            }),
            
            // Requisitos
            'requirements' => $program->requirements->map(function ($requirement) {
                return [
                    'id' => $requirement->id,
                    'name' => $requirement->name,
                    'description' => $requirement->description ?? '',
                    'is_mandatory' => $requirement->pivot->type === 'mandatory',
                ];
            }),
        ];

        return $programData;
    }

    private function calculateDuration($departureDate)
    {
        if (!$departureDate) return 'Duración no especificada';
        
        $today = new \DateTime();
        $departure = new \DateTime($departureDate);
        $diff = $today->diff($departure);
        
        if ($diff->days === 0) {
            return 'Hoy';
        } elseif ($diff->days === 1) {
            return '1 día';
        } elseif ($diff->days < 7) {
            return $diff->days . ' días';
        } elseif ($diff->days < 30) {
            $weeks = ceil($diff->days / 7);
            return $weeks . ' semana' . ($weeks > 1 ? 's' : '');
        } else {
            $months = ceil($diff->days / 30);
            return $months . ' mes' . ($months > 1 ? 'es' : '');
        }
    }
}
