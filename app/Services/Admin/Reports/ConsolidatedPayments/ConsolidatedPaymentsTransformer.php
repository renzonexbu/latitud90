<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsolidatedPaymentsTransformer
{
    public function transformForView(LengthAwarePaginator $data): LengthAwarePaginator
    {
        $data->getCollection()->transform(function ($item) {
            return $this->transformPaymentItem($item);
        });
        
        return $data;
    }
    
    public function transformForExport(Collection $data, array $selectedFields = []): Collection
    {
        return $data->map(function ($item) use ($selectedFields) {
            return $this->transformForExportRow($item, $selectedFields);
        });
    }
    
    private function transformPaymentItem($item): array
    {
        return [
            'id' => $item->order_id,
            'program_id' => $item->program_id ?? 'N/A',
            'program_code' => $item->program_code ?? 'N/A',
            'participant_rut' => $this->formatDocument($item->participant_rut ?? 'N/A'),
            'participant_name' => $this->buildParticipantName($item),
            'authorization_code' => $item->authorization_code ?? 'N/A',
            'payment_amount' => (float) ($item->payment_amount ?? 0),
            'payment_status' => $item->payment_status ?? 'N/A',
            // Documentos: prioridad Bsale, luego buy_order
            'invoice_number' => $item->bsale_number ?? $item->buy_order ?? 'N/A',
            'document_type_code' => $item->document_type ?? 'N/A',
            'payment_method_code' => $item->payment_method_code ?? 'N/A',
            'payment_method_name' => $this->capitalizeWords($this->cleanUtf8($item->payment_method_name ?? 'N/A')),
            'installments_number' => $item->installments_number ?? 1,
            'paid_installments' => (int)($item->paid_installments ?? 0),
            'total_installments' => (int)($item->total_installments ?? 0),
            'paid_installments_display' => $this->formatInstallments(
                (int)($item->paid_installments ?? 0),
                (int)($item->total_installments ?? 0)
            ),
            'payment_date' => $item->payment_date,
            'payer_name' => $this->capitalizeWords($this->cleanUtf8($item->payer_name ?? 'N/A')),
            'payer_email' => $this->cleanUtf8($item->payer_email ?? 'N/A'),
            'is_refund' => ($item->payment_amount ?? 0) < 0,
            'reservation_number' => $item->order_number ?? $item->buy_order ?? 'N/A',
            'external_contribution' => (float) ($item->scholarship_amount ?? 0),
            'released' => (bool) ($item->released ?? false),
            'total_program_value' => (float) ($item->program_total_value ?? 0),
        ];
    }
    
    private function transformForExportRow($item, array $selectedFields = []): array
    {
        $transformed = $this->transformPaymentItem($item);
        $row = [];
        
        // Campos de identificación
        if (isset($selectedFields['identification'])) {
            if (in_array('programId', $selectedFields['identification'])) {
                $row['ID Programa'] = $transformed['program_id'];
            }
            if (in_array('authorizationCode', $selectedFields['identification'])) {
                $row['N° Autorización'] = $transformed['authorization_code'];
            }
            if (in_array('participantRut', $selectedFields['identification'])) {
                $row['RUT Participante'] = $transformed['participant_rut'];
            }
        }
        
        // Campos del pago
        if (isset($selectedFields['payment'])) {
            if (in_array('amount', $selectedFields['payment'])) {
                $row['Pago o Devolución'] = $transformed['payment_amount'];
            }
            if (in_array('invoiceNumber', $selectedFields['payment'])) {
                $row['N° de Boleta'] = $transformed['invoice_number'];
            }
            if (in_array('paymentMethod', $selectedFields['payment'])) {
                $row['Forma de Pago'] = $transformed['payment_method_code'];
            }
            if (in_array('installmentsNumber', $selectedFields['payment'])) {
                $row['N° de Cuotas'] = $transformed['installments_number'];
            }
            if (in_array('paymentDate', $selectedFields['payment'])) {
                $row['Fecha de Pago'] = $this->formatDate($transformed['payment_date']);
            }
        }
        
        // Campos del pagador
        if (isset($selectedFields['payer'])) {
            if (in_array('name', $selectedFields['payer'])) {
                $row['Nombre Contacto Pagador'] = $transformed['payer_name'];
            }
            if (in_array('email', $selectedFields['payer'])) {
                $row['E-mail Contacto Pagador'] = $transformed['payer_email'];
            }
        }
        
        // Si no hay campos seleccionados, incluir todos por defecto
        if (empty($selectedFields)) {
            $row = [
                'ID Programa' => $transformed['program_id'],
                'N° Autorización' => $transformed['authorization_code'],
                'RUT Participante' => $transformed['participant_rut'],
                'Pago o Devolución' => $transformed['payment_amount'],
                'N° de Boleta' => $transformed['invoice_number'],
                'Forma de Pago' => $transformed['payment_method_code'],
                'N° de Cuotas' => $transformed['installments_number'],
                'Fecha de Pago' => $this->formatDate($transformed['payment_date']),
                'Nombre Contacto Pagador' => $transformed['payer_name'],
                'E-mail Contacto Pagador' => $transformed['payer_email'],
            ];
        }
        
        return $row;
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

    private function capitalizeWords(string $text): string
    {
        return ucwords(strtolower($text));
    }

    private function buildParticipantName($item): string
    {
        $parts = [];
        if (!empty($item->first_name)) $parts[] = $this->capitalizeWords($this->cleanUtf8($item->first_name));
        if (!empty($item->second_name)) $parts[] = $this->capitalizeWords($this->cleanUtf8($item->second_name));
        if (!empty($item->first_last_name)) $parts[] = $this->capitalizeWords($this->cleanUtf8($item->first_last_name));
        if (!empty($item->second_last_name)) $parts[] = $this->capitalizeWords($this->cleanUtf8($item->second_last_name));
        return !empty($parts) ? implode(' ', $parts) : 'N/A';
    }

    private function formatInstallments(int $paid, int $total): string
    {
        if ($total <= 0) return (string)$paid;
        return sprintf('%d/%d', $paid, $total);
    }

    private function formatDocument($documentNumber): string
    {
        if (empty($documentNumber)) {
            return 'N/A';
        }

        // Detectar automáticamente si es RUT por formato
        $cleanNumber = str_replace(['.', '-'], '', $documentNumber);
        if (preg_match('/^\d{7,8}[\dK]$/', $cleanNumber)) {
            // Es un RUT, formatear como RUT
            $body = substr($cleanNumber, 0, -1);
            $dv = substr($cleanNumber, -1);
            $withDots = number_format($body, 0, '', '.');
            return $withDots . '-' . strtoupper($dv);
        } else {
            // Es un pasaporte u otro documento, mostrar tal como está
            return $this->cleanUtf8($documentNumber);
        }
    }
}
