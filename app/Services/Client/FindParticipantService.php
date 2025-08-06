<?php

namespace App\Services\Client;

use App\Models\Participant;

class FindParticipantService
{
    public function findByRut(string $rut): ?Participant
    {
        // Limpiar el RUT de puntos y guiones
        $cleanRut = $this->cleanRut($rut);
        
        // Buscar el participante en la base de datos
        $participant = Participant::where('document_number', $cleanRut)
            ->where('document_type', 'RUT')
            ->first();
            
        return $participant;
    }
    
    private function cleanRut(string $rut): string
    {
        // Remover puntos y guiones del RUT
        return preg_replace('/[.-]/', '', $rut);
    }
}
