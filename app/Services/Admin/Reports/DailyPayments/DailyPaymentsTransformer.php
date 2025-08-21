<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DailyPaymentsTransformer
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
        // Calcular montos adicionales (por ahora 0 ya que no existen las tablas order_movements ni external_contributions)
        $releasedAmount = 0; // $this->calculateReleasedAmount($item->order_id);
        $externalContribution = 0; // $this->calculateExternalContribution($item->order_id);
        $remainingBalance = ($item->final_amount ?? 0) - ($item->payment_amount ?? 0) - $externalContribution;

        // Construir nombre del participante con CapitalCase
        $participantName = $this->buildParticipantName($item);

        // Obtener información del apoderado (contacto de emergencia)
        $apoderadoInfo = $this->getApoderadoInfo($item->order_id);

        return [
            'id' => $item->payment_id,
            'order_id' => $item->order_id,
            'order_number' => $item->order_number ?? 'N/A',
            'participant_name' => $participantName,
            'participant_email' => $this->cleanUtf8($item->email ?? ''),
            'participant_document' => $this->formatDocument($item->document_number ?? ''),
            'participant_phone' => $this->cleanUtf8($item->phone ?? ''),
            'program_name' => $this->cleanUtf8($item->program_name ?? 'N/A'),
            'program_destination' => $this->cleanUtf8($item->destination ?? ''),
            'program_departure_date' => $item->departure_date,
            'program_price' => (float) ($item->total_amount ?? 0),
            'sales_executive_name' => $this->capitalizeWords($this->cleanUtf8($item->sales_executive_name ?? 'N/A')),
            'sales_executive_email' => $this->cleanUtf8($item->sales_executive_email ?? ''),
            'sales_executive_phone' => $this->cleanUtf8($item->sales_executive_phone ?? ''),
            'financing_type' => $item->payment_type ?? 'N/A',
            'financing_type_label' => $this->getFinancingTypeLabel($item->payment_type ?? ''),
            'payment_amount' => (float) ($item->payment_amount ?? 0),
            'payment_date' => $item->installment_paid_at ?? $item->payment_date,
            'payment_status' => $item->payment_status ?? 'N/A',
            'payment_method_name' => $this->capitalizeWords($this->cleanUtf8($item->payment_gateway_name ?? 'N/A')),
            'payment_method_code' => $item->payment_gateway_code ?? 'N/A',
            'released_amount' => (float) $releasedAmount,
            'external_contribution' => (float) $externalContribution,
            'remaining_balance' => (float) $remainingBalance,
            'order_date' => $item->order_date,
            // Datos del pagador (desde orders_detail)
            'payer_name' => $this->capitalizeWords($this->cleanUtf8($item->payer_name ?? '')),
            'payer_email' => $this->cleanUtf8($item->payer_email ?? ''),
            'payer_phone' => $this->cleanUtf8($item->payer_phone ?? ''),
            'payer_document' => $this->formatDocument($item->payer_document ?? ''),
            // Datos de la cuota
            'installment_number' => $item->installment_number ?? 'N/A',
            'installment_amount' => (float) ($item->installment_amount ?? 0),
            'installment_due_date' => $item->installment_due_date,
            'installment_status' => $item->installment_status ?? 'N/A',
            'installment_paid_at' => $item->installment_paid_at,
            // Información del contacto de emergencia (apoderado)
            'emergency_contact_name' => $this->capitalizeWords($this->cleanUtf8($apoderadoInfo['name'])),
            'emergency_contact_phone' => $this->cleanUtf8($apoderadoInfo['phone']),
            'emergency_contact_email' => $this->cleanUtf8($apoderadoInfo['email']),
        ];
    }
    
    private function transformForExportRow($item, array $selectedFields = []): array
    {
        $transformed = $this->transformPaymentItem($item);
        $row = [];
        
        // Campos del participante
        if (isset($selectedFields['participant'])) {
            if (in_array('name', $selectedFields['participant'])) {
                $row['Participante'] = $transformed['participant_name'];
            }
            if (in_array('email', $selectedFields['participant'])) {
                $row['Email'] = $transformed['participant_email'];
            }
            if (in_array('document', $selectedFields['participant'])) {
                $row['Documento'] = $transformed['participant_document'];
            }
            if (in_array('phone', $selectedFields['participant'])) {
                $row['Teléfono'] = $transformed['participant_phone'];
            }
        }
        
        // Campos del programa
        if (isset($selectedFields['program'])) {
            if (in_array('name', $selectedFields['program'])) {
                $row['Programa'] = $transformed['program_name'];
            }
            if (in_array('destination', $selectedFields['program'])) {
                $row['Destino'] = $transformed['program_destination'];
            }
            if (in_array('departureDate', $selectedFields['program'])) {
                $row['Fecha Salida'] = $this->formatDate($transformed['program_departure_date']);
            }
            if (in_array('price', $selectedFields['program'])) {
                $row['Precio Programa'] = $transformed['program_price'];
            }
            if (in_array('salesExecutive', $selectedFields['program'])) {
                $row['Ejecutivo de Ventas'] = $transformed['sales_executive_name'];
            }
            if (in_array('salesExecutiveEmail', $selectedFields['program'])) {
                $row['Email Ejecutivo'] = $transformed['sales_executive_email'];
            }
            if (in_array('salesExecutivePhone', $selectedFields['program'])) {
                $row['Teléfono Ejecutivo'] = $transformed['sales_executive_phone'];
            }
        }
        
        // Campos del pago
        if (isset($selectedFields['payment'])) {
            if (in_array('orderNumber', $selectedFields['payment'])) {
                $row['N° Orden'] = $transformed['order_number'];
            }
            if (in_array('financingType', $selectedFields['payment'])) {
                $row['Forma Financiamiento'] = $transformed['financing_type_label'];
            }
            if (in_array('amount', $selectedFields['payment'])) {
                $row['Monto Abonado'] = $transformed['payment_amount'];
            }
            if (in_array('releasedAmount', $selectedFields['payment'])) {
                $row['Monto Liberado'] = $transformed['released_amount'];
            }
            if (in_array('externalContribution', $selectedFields['payment'])) {
                $row['Aporte Externo'] = $transformed['external_contribution'];
            }
            if (in_array('remainingBalance', $selectedFields['payment'])) {
                $row['Saldo por Pagar'] = $transformed['remaining_balance'];
            }
            if (in_array('paymentDate', $selectedFields['payment'])) {
                $row['Fecha Pago'] = $this->formatDate($transformed['payment_date']);
            }
            if (in_array('paymentMethod', $selectedFields['payment'])) {
                $row['Método Pago'] = $transformed['payment_method_name'];
            }
            if (in_array('status', $selectedFields['payment'])) {
                $row['Estado Pago'] = $transformed['payment_status'];
            }
        }

        // Campos del apoderado
        if (isset($selectedFields['apoderado'])) {
            if (in_array('name', $selectedFields['apoderado'])) {
                $row['Nombre Apoderado'] = $transformed['emergency_contact_name'];
            }
            if (in_array('email', $selectedFields['apoderado'])) {
                $row['Email Apoderado'] = $transformed['emergency_contact_email'];
            }
            if (in_array('phone', $selectedFields['apoderado'])) {
                $row['Teléfono Apoderado'] = $transformed['emergency_contact_phone'];
            }
        }
        
        // Si no hay campos seleccionados, incluir todos por defecto
        if (empty($selectedFields)) {
            $row = [
                'Participante' => $transformed['participant_name'],
                'Email' => $transformed['participant_email'],
                'Documento' => $transformed['participant_document'],
                'Teléfono' => $transformed['participant_phone'],
                'Programa' => $transformed['program_name'],
                'Destino' => $transformed['program_destination'],
                'Fecha Salida' => $this->formatDate($transformed['program_departure_date']),
                'Precio Programa' => $transformed['program_price'],
                'Ejecutivo de Ventas' => $transformed['sales_executive_name'],
                'Email Ejecutivo' => $transformed['sales_executive_email'],
                'Teléfono Ejecutivo' => $transformed['sales_executive_phone'],
                'N° Orden' => $transformed['order_number'],
                'Forma Financiamiento' => $transformed['financing_type_label'],
                'Monto Abonado' => $transformed['payment_amount'],
                'Monto Liberado' => $transformed['released_amount'],
                'Aporte Externo' => $transformed['external_contribution'],
                'Saldo por Pagar' => $transformed['remaining_balance'],
                'Fecha Pago' => $this->formatDate($transformed['payment_date']),
                'Método Pago' => $transformed['payment_method_name'],
                'Estado Pago' => $transformed['payment_status'],
            ];
        }
        
        return $row;
    }
    
    /*
    private function calculateReleasedAmount($orderId): float
    {
        // Buscar movimientos con tipo "LIBERADO" para esta orden
        // Tabla order_movements no existe en la base de datos
        $releasedAmount = DB::table('order_movements')
            ->where('order_id', $orderId)
            ->where('type', 'LIBERADO')
            ->sum('amount');
            
        return (float) $releasedAmount;
    }
    
    private function calculateExternalContribution($orderId): float
    {
        // Buscar aportes externos para esta orden
        // Tabla external_contributions no existe en la base de datos
        $externalContribution = DB::table('external_contributions')
            ->where('order_id', $orderId)
            ->sum('amount');
            
        return (float) $externalContribution;
    }
    */
    
    private function getFinancingTypeLabel(string $financingType): string
    {
        $labels = [
            'total' => 'Pago Único',
            'monthly' => 'Pago en Cuotas',
        ];
        
        return $labels[$financingType] ?? $financingType;
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

    private function buildParticipantName($item): string
    {
        $firstName = $this->cleanUtf8($item->first_name ?? '');
        $secondName = $this->cleanUtf8($item->second_name ?? '');
        $lastName = $this->cleanUtf8($item->first_last_name ?? '');
        $secondLastName = $this->cleanUtf8($item->second_last_name ?? '');

        $nameParts = [];
        if ($firstName) {
            $nameParts[] = $this->capitalizeWords($firstName);
        }
        if ($secondName) {
            $nameParts[] = $this->capitalizeWords($secondName);
        }
        if ($lastName) {
            $nameParts[] = $this->capitalizeWords($lastName);
        }
        if ($secondLastName) {
            $nameParts[] = $this->capitalizeWords($secondLastName);
        }

        return implode(' ', $nameParts);
    }

    /**
     * Aplicar CapitalCase a un string
     */
    private function capitalizeWords(string $text): string
    {
        return ucwords(strtolower(trim($text)));
    }

    private function getApoderadoInfo($orderId): array
    {
        // Obtener el participant_id desde la orden
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) return ['name' => 'N/A', 'phone' => 'N/A', 'email' => 'N/A'];
        
        // Buscar el contacto de emergencia para este participante
        $emergencyContact = DB::table('emergency_contact')
            ->where('participant_id', $order->participant_id)
            ->first();

        return $emergencyContact ? [
            'name' => $this->capitalizeWords($this->cleanUtf8($emergencyContact->name)),
            'phone' => $this->cleanUtf8($emergencyContact->phone),
            'email' => $this->cleanUtf8($emergencyContact->email)
        ] : ['name' => 'N/A', 'phone' => 'N/A', 'email' => 'N/A'];
    }

    /**
     * Formatea el número de documento según su tipo
     */
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
