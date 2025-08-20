<?php

namespace App\Services\Admin\Reports\RecoverySchedule;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RecoveryScheduleTransformer
{
    public function transformForView(LengthAwarePaginator $data): LengthAwarePaginator
    {
        $data->getCollection()->transform(function ($item) {
            return $this->transformScheduleItem($item);
        });
        
        return $data;
    }
    
    public function transformForExport(Collection $data): Collection
    {
        return $data->map(function ($item) {
            return $this->transformForExportRow($item);
        });
    }
    
    private function transformScheduleItem($item): array
    {
        return [
            'id' => $item->id,
            'participant_name' => $this->cleanUtf8($item->first_name . ' ' . $item->last_name),
            'participant_email' => $this->cleanUtf8($item->email),
            'participant_document' => $this->cleanUtf8($item->document_number),
            'participant_phone' => $this->cleanUtf8($item->phone),
            'program_name' => $this->cleanUtf8($item->program_name),
            'program_departure_date' => $item->program_departure_date,
            'installment_number' => $item->installment_number,
            'due_date' => $item->due_date,
            'amount' => (float) $item->amount,
            'base_amount' => (float) $item->base_amount,
            'discount_amount' => (float) $item->discount_amount,
            'status' => $this->determinePaymentStatus($item),
            'days_overdue' => (int) $item->days_overdue,
            'days_until_due' => (int) $item->days_until_due,
            'paid_amount' => (float) $item->paid_amount,
            'pending_amount' => (float) $item->pending_amount,
            'is_paid' => (bool) $item->is_paid,
            'paid_at' => $item->paid_at,
            'order_number' => $item->order_number,
            'order_total_amount' => (float) $item->order_total_amount,
            'order_final_amount' => (float) $item->order_final_amount,
        ];
    }
    
    private function transformForExportRow($item): array
    {
        $transformed = $this->transformScheduleItem($item);
        
        return [
            'Participante' => $transformed['participant_name'],
            'Email' => $transformed['participant_email'],
            'Documento' => $transformed['participant_document'],
            'Teléfono' => $transformed['participant_phone'],
            'Programa' => $transformed['program_name'],
            'Fecha Salida' => $this->formatDate($transformed['program_departure_date']),
            'N° Cuota' => $transformed['installment_number'],
            'Fecha Vencimiento' => $this->formatDate($transformed['due_date']),
            'Monto Cuota' => $transformed['amount'],
            'Monto Base' => $transformed['base_amount'],
            'Descuento' => $transformed['discount_amount'],
            'Estado' => $this->getStatusLabel($transformed['status']),
            'Días Vencimiento' => $transformed['days_overdue'],
            'Monto Pagado' => $transformed['paid_amount'],
            'Monto Pendiente' => $transformed['pending_amount'],
            'N° Orden' => $transformed['order_number'],
        ];
    }
    
    private function determinePaymentStatus($item): string
    {
        // Usar el status calculado del DataProvider
        if (isset($item->calculated_status)) {
            return $item->calculated_status;
        }
        
        // Si ya está pagada, retornar paid
        if ($item->is_paid) {
            return 'paid';
        }
        
        // Si tiene días de vencimiento, retornar overdue
        if ($item->days_overdue > 0) {
            return 'overdue';
        }
        
        // Si está próxima a vencer (30 días o menos), retornar upcoming
        if ($item->days_until_due <= 30) {
            return 'upcoming';
        }
        
        // Por defecto, pendiente
        return 'pending';
    }
    
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Pendiente',
            'overdue' => 'Vencida',
            'paid' => 'Pagada',
            'upcoming' => 'Próxima',
        ];
        
        return $labels[$status] ?? $status;
    }
    
    private function formatDate($date): string
    {
        if (!$date) return 'N/A';
        return \Carbon\Carbon::parse($date)->format('d/m/Y');
    }
    
    private function cleanUtf8(string $text): string
    {
        if (!$text) return '';
        
        // Detectar encoding
        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'ASCII'], true);
        
        if ($encoding === false) {
            $encoding = 'ISO-8859-1';
        }
        
        // Convertir a UTF-8 si es necesario
        if ($encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }
        
        return $text;
    }
}
