<?php

namespace App\Services\Admin\Reports\TermsAcceptance;

use App\Models\OrderDetail;
use App\Models\Document;
use Illuminate\Support\Collection;

class TermsAcceptanceDataProvider
{
    /**
     * Mapeo de tipos de documento
     */
    private function getDocumentTypesMap(): array
    {
        return Document::pluck('name', 'id')->toArray();
    }

    /**
     * Formatear RUT chileno
     */
    private function formatRut(?string $rut): string
    {
        if (!$rut) return '';

        $cleanRut = preg_replace('/[^0-9kK]/', '', $rut);

        if (strlen($cleanRut) < 2) return $cleanRut;

        $body = substr($cleanRut, 0, -1);
        $dv = strtoupper(substr($cleanRut, -1));

        $formattedBody = number_format((int)$body, 0, '', '.');

        return "{$formattedBody}-{$dv}";
    }

    /**
     * Formatear nombre con apellido primero: "Apellidos, Nombres"
     * Para nombres completos sin campos separados (ej: pagador)
     * Heurística: en nombres chilenos de 4 partes, las últimas 2 son apellidos;
     * en 3 partes, la última 2 son apellidos; en 2 partes, la última es apellido.
     */
    private function formatNameLastNameFirst(?string $name): string
    {
        if (!$name) return '';

        $parts = preg_split('/\s+/', trim($name));
        $parts = array_map(fn($p) => ucwords(strtolower($p)), $parts);

        if (count($parts) <= 1) {
            return implode(' ', $parts);
        }

        if (count($parts) === 2) {
            return "{$parts[1]}, {$parts[0]}";
        }

        // 3+ partes: las últimas 2 son apellidos
        $lastNames = array_slice($parts, -2);
        $firstNames = array_slice($parts, 0, count($parts) - 2);

        return implode(' ', $lastNames) . ', ' . implode(' ', $firstNames);
    }

    /**
     * Formatear nombre del participante con apellido primero usando campos separados
     */
    private function formatParticipantLastNameFirst($participant): string
    {
        if (!$participant) return 'N/A';

        $lastNames = [];
        if ($participant->first_last_name) {
            $lastNames[] = ucwords(strtolower(trim($participant->first_last_name)));
        }
        if ($participant->second_last_name) {
            $lastNames[] = ucwords(strtolower(trim($participant->second_last_name)));
        }

        $firstNames = [];
        if ($participant->first_name) {
            $firstNames[] = ucwords(strtolower(trim($participant->first_name)));
        }
        if ($participant->second_name) {
            $firstNames[] = ucwords(strtolower(trim($participant->second_name)));
        }

        if (empty($lastNames) && empty($firstNames)) return 'N/A';
        if (empty($lastNames)) return implode(' ', $firstNames);
        if (empty($firstNames)) return implode(' ', $lastNames);

        return implode(' ', $lastNames) . ', ' . implode(' ', $firstNames);
    }

    /**
     * Obtener todos los registros de aceptación de términos
     */
    public function getData(array $filters = []): Collection
    {
        $documentTypes = $this->getDocumentTypesMap();
        $query = OrderDetail::query()
            ->whereNotNull('terms_accepted_at')
            ->where('terms_accepted', true)
            ->with(['order.programCourse', 'order.participant', 'termsCondition']);

        // Filtro por programa
        if (!empty($filters['program_id'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('program_id', $filters['program_id']);
            });
        }

        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $query->whereDate('terms_accepted_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('terms_accepted_at', '<=', $filters['date_to']);
        }

        // Búsqueda por nombre, documento o email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('order.participant', function ($q2) use ($search) {
                        $q2->where('first_name', 'like', "%{$search}%")
                            ->orWhere('first_last_name', 'like', "%{$search}%")
                            ->orWhere('second_last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Ordenar por fecha de aceptación descendente
        $query->orderBy('terms_accepted_at', 'desc');

        return $query->get()->map(function ($orderDetail) use ($documentTypes) {
            // Obtener tipo de documento
            $documentTypeId = $orderDetail->document_type;
            $documentTypeName = $documentTypes[$documentTypeId] ?? $orderDetail->document_type;

            // Formatear RUT si corresponde
            if (strtoupper($documentTypeName) === 'RUT') {
                $documentNumber = $this->formatRut($orderDetail->document_number ?? '');
            } else {
                $documentNumber = $orderDetail->document_number ?? '';
            }

            $participant = $orderDetail->order?->participant;

            return [
                'id' => $orderDetail->id,
                'order_id' => $orderDetail->order_id,
                'pagador_name' => $this->formatNameLastNameFirst($orderDetail->name),
                'document_type' => $documentTypeName,
                'document_number' => $documentNumber,
                'participant_name' => $this->formatParticipantLastNameFirst($participant),
                'program_code' => $orderDetail->order?->programCourse?->code ?? 'N/A',
                'program_name' => $orderDetail->order?->programCourse?->name ?? 'N/A',
                'terms_accepted_at' => $orderDetail->terms_accepted_at?->format('d/m/Y H:i:s'),
                'terms_accepted_date' => $orderDetail->terms_accepted_at?->format('d/m/Y'),
                'terms_accepted_time' => $orderDetail->terms_accepted_at?->format('H:i:s'),
                'email' => $orderDetail->email,
            ];
        });
    }
}
