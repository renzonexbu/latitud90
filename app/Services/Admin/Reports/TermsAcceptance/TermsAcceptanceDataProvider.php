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
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Ordenar por fecha de aceptación descendente
        $query->orderBy('terms_accepted_at', 'desc');

        return $query->get()->map(function ($orderDetail) use ($documentTypes) {
            // Obtener tipo de documento
            $documentTypeId = $orderDetail->document_type;
            $documentTypeName = $documentTypes[$documentTypeId] ?? $orderDetail->document_type;

            // Si es RUT, formatearlo. Si es otro tipo de documento, usar RUT genérico
            if (strtoupper($documentTypeName) === 'RUT') {
                $documentNumber = $this->formatRut($orderDetail->document_number ?? '');
            } else {
                $documentNumber = '11.111.111-1';
            }

            return [
                'id' => $orderDetail->id,
                'order_id' => $orderDetail->order_id,
                'name' => $orderDetail->name,
                'document_type' => $documentTypeName,
                'document_number' => $documentNumber,
                'email' => $orderDetail->email,
                'terms_accepted_at' => $orderDetail->terms_accepted_at?->format('d/m/Y H:i:s'),
                'terms_accepted_date' => $orderDetail->terms_accepted_at?->format('d/m/Y'),
                'terms_accepted_time' => $orderDetail->terms_accepted_at?->format('H:i:s'),
                'terms_accepted_at_raw' => $orderDetail->terms_accepted_at,
                'ip_address' => $orderDetail->ip_address,
                'browser' => $orderDetail->browser,
                'operating_system' => $orderDetail->operating_system,
                'device_type' => $orderDetail->device_type,
                'user_agent' => $orderDetail->user_agent,
                'geo_country' => $orderDetail->geo_country,
                'geo_city' => $orderDetail->geo_city,
                'terms_accepted' => $orderDetail->terms_accepted,
                'terms_accepted_confirmation' => $orderDetail->terms_accepted_confirmation,
                'program_name' => $orderDetail->order?->programCourse?->name ?? 'N/A',
                'program_code' => $orderDetail->order?->programCourse?->code ?? 'N/A',
                'participant_name' => $orderDetail->order?->participant?->full_name ?? 'N/A',
                // Información de versión de T&C
                'tc_version' => $orderDetail->termsCondition?->version ?? 'N/A',
                'tc_effective_date' => $orderDetail->termsCondition?->effective_date?->format('d/m/Y') ?? 'N/A',
                'tc_title' => $orderDetail->termsCondition?->title ?? 'N/A',
            ];
        });
    }
}
