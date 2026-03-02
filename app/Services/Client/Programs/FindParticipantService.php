<?php

namespace App\Services\Client\Programs;

use App\Models\Participant;
use App\Models\Document;
use App\Traits\SystemLogging;

class FindParticipantService
{
    use SystemLogging;
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

        if (!$participant) {
            return null;
        }

        // Verificar que tenga al menos un programa activo (no todos en baja)
        $hasActiveProgram = \DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->where('is_active', true)
            ->exists();

        $hasAnyProgram = \DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->exists();

        // Si tiene programas pero ninguno activo, no lo retornamos (está en baja)
        if ($hasAnyProgram && !$hasActiveProgram) {
            return null;
        }

        return $participant;
    }
    
    public function findByRut(string $rut): ?Participant
    {
        // Mantener compatibilidad con el método anterior
        return $this->findByDocument($rut, 'RUT');
    }

    /**
     * Verificar si un participante existe pero tiene todos sus programas en baja
     * (participant_program.is_active = false en todas sus inscripciones)
     */
    public function existsInactiveByDocument(string $documentNumber, ?string $documentType = null): bool
    {
        if (!$documentType) {
            $documentType = $this->detectDocumentType($documentNumber);
        }

        $cleanDocument = $this->cleanDocument($documentNumber);

        $participant = Participant::join('document', 'participants.document_type', '=', 'document.id')
            ->where('participants.document_number', $cleanDocument)
            ->where('document.name', $documentType)
            ->select('participants.id', 'participants.is_active')
            ->first();

        if (!$participant) {
            return false;
        }

        // Si el participante mismo está inactivo, es baja
        if (!$participant->is_active) {
            return true;
        }

        // Verificar si tiene programas y todos están en baja
        $totalPrograms = \DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->count();

        if ($totalPrograms === 0) {
            return false;
        }

        $activePrograms = \DB::table('participant_program')
            ->where('participant_id', $participant->id)
            ->where('is_active', true)
            ->count();

        return $activePrograms === 0;
    }
    
    private function detectDocumentType(string $documentNumber): string
    {
        // Limpiar el documento
        $clean = $this->cleanDocument($documentNumber);
        
        // Detectar si es RUT (formato: 12345678-9 o 12.345.678-9)
        if (preg_match('/^\d{7,8}[\dK]$/', $clean)) {
            return 'RUT';
        }
        
        // Detectar si es DNI (solo números, 7-8 dígitos)
        if (preg_match('/^\d{7,8}$/', $clean)) {
            return 'DNI';
        }
        
        // Si no es RUT ni DNI, asumir que es PASAPORTE
        return 'PASAPORTE';
    }
    
    private function cleanDocument(string $document): string
    {
        // Remover puntos y guiones del documento
        return preg_replace('/[.-]/', '', $document);
    }
}
