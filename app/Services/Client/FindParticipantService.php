<?php

namespace App\Services\Client;

use App\Models\Participant;
use App\Models\Document;

class FindParticipantService
{
    public function findByDocument(string $documentNumber, ?string $documentType = null): ?Participant
    {
        // Si no se especifica el tipo de documento, detectarlo automáticamente
        if (!$documentType) {
            $documentType = $this->detectDocumentType($documentNumber);
        }
        
        // Limpiar el documento de puntos y guiones
        $cleanDocument = $this->cleanDocument($documentNumber);
        
        // Buscar el participante en la base de datos usando join con la tabla document
        // Solo buscar participantes activos
        $participant = Participant::join('document', 'participants.document_type', '=', 'document.id')
            ->where('participants.document_number', $cleanDocument)
            ->where('document.name', $documentType)
            ->where('participants.is_active', true)
            ->select('participants.*')
            ->first();
        return $participant;
    }
    
    public function findByRut(string $rut): ?Participant
    {
        // Mantener compatibilidad con el método anterior
        return $this->findByDocument($rut, 'RUT');
    }
    
    private function detectDocumentType(string $documentNumber): string
    {
        // Limpiar el documento
        $clean = $this->cleanDocument($documentNumber);
        
        // Detectar si es RUT (formato: 12345678-9 o 12.345.678-9)
        if (preg_match('/^\d{7,8}[\dK]$/', $clean)) {
            return 'RUT';
        }
        
        // Si no es RUT, asumir que es PASAPORTE
        return 'PASAPORTE';
    }
    
    private function cleanDocument(string $document): string
    {
        // Remover puntos y guiones del documento
        return preg_replace('/[.-]/', '', $document);
    }
}
