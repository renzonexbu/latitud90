<?php

namespace App\Services\Admin\Subscriptions\ChargeAttempts;

use Illuminate\Support\Collection;
use Carbon\Carbon;

class ChargeAttemptsTransformer
{
    /**
     * Transformar datos para la vista
     */
    public function transformForView(Collection $data): Collection
    {
        return $data->map(function ($item) {
            return (object) [
                // IDs
                'charge_attempt_id' => $item->charge_attempt_id,
                'subscription_id' => $item->subscription_id,
                'program_subscription_id' => $item->program_subscription_id,
                'virtualpos_plan_id' => $item->virtualpos_plan_id,
                'installment_id' => $item->installment_id,

                // Plan
                'plan_code' => $item->plan_code,
                'plan_name' => $item->plan_name ?? 'N/A',
                'plan_description' => $item->plan_description,

                // Participante
                'participant_id' => $item->participant_id,
                'participant_name' => $this->toTitleCase(trim($item->participant_name)),
                'participant_document' => $item->participant_document,
                'document_type' => $item->document_type ?? 'N/A',

                // Programa
                'program_id' => $item->program_id,
                'program_name' => $item->program_name,
                'program_course_id' => $item->program_course_id,
                'program_departure_date' => $item->program_departure_date,

                // Ejecutivo
                'sales_executive_id' => $item->sales_executive_id,
                'sales_executive_name' => $item->sales_executive_name ?? 'Sin asignar',

                // Cuota
                'installment_number' => $item->installment_number,
                'installment_due_date' => $item->installment_due_date,
                'installment_status' => $item->installment_status,
                'installment_is_paid' => (bool) $item->installment_is_paid,
                'installment_payment_source' => $item->installment_payment_source,
                'installment_payment_source_label' => $this->getPaymentSourceLabel($item->installment_payment_source),

                // Intento de cobro
                'virtualpos_charge_id' => $item->virtualpos_charge_id,
                'original_charge_id' => $item->original_charge_id,
                'attempt_number' => $item->attempt_number,
                'amount' => (float) $item->amount,
                'amount_formatted' => '$' . number_format($item->amount, 0, ',', '.'),
                'description' => $item->description,
                'attempt_type' => $item->attempt_type,
                'attempt_type_label' => $this->getAttemptTypeLabel($item->attempt_type),
                'charge_status' => $item->charge_status,
                'charge_status_label' => $this->getChargeStatusLabel($item->charge_status),
                'failure_reason' => $item->failure_reason,
                'virtualpos_status' => $item->virtualpos_status,
                'attempted_at' => $item->attempted_at,
                'attempted_at_formatted' => $item->attempted_at ? Carbon::parse($item->attempted_at)->format('d-m-Y H:i') : null,
                'resolved_at' => $item->resolved_at,
                'resolved_at_formatted' => $item->resolved_at ? Carbon::parse($item->resolved_at)->format('d-m-Y H:i') : null,
                'created_at' => $item->created_at,

                // Suscripción
                'subscription_status' => $item->subscription_status,
                'virtualpos_subscription_id' => $item->virtualpos_subscription_id,

                // Display completo
                'charge_display' => $this->formatChargeDisplay($item),
                'installment_display' => $this->formatInstallmentDisplay($item),
            ];
        });
    }

    /**
     * Transformar datos para exportación
     */
    public function transformForExport(Collection $data): Collection
    {
        return $data->map(function ($item) {
            return [
                'ID Suscripción' => $item->subscription_id ?? '',
                'ID Plan VirtualPos' => $item->virtualpos_plan_id ?? '',
                'Código Plan' => $item->plan_code ?? '',
                'Nombre Plan' => $item->plan_name ?? '',

                // Información de la cuota
                'ID Cuota' => $item->installment_id ?? '',
                'Número Cuota' => $item->installment_number ?? '',
                'Monto Cuota' => (float) $item->amount,
                'Fecha Vencimiento' => $item->installment_due_date,
                'Estado Cuota' => $this->getInstallmentStatusLabel($item->installment_status),
                'Cuota Pagada' => $item->installment_is_paid ? 'Sí' : 'No',
                'Fecha Pago' => $item->paid_at ? Carbon::parse($item->paid_at)->format('d-m-Y H:i') : '',
                'Fuente Pago' => $this->getPaymentSourceLabel($item->installment_payment_source),

                // Información del participante
                'Participante' => $this->toTitleCase(trim($item->participant_name)),
                'Documento' => $item->participant_document,
                'Tipo Documento' => $item->document_type ?? '',

                // Información del programa
                'Programa' => $item->program_name,
                'Fecha Salida' => $item->program_departure_date,
                'Ejecutivo' => $item->sales_executive_name ?? 'Sin asignar',

                // Información del intento de cobro (si existe)
                'ID Intento Cobro' => $item->charge_attempt_id ?? '',
                'Intento N°' => $item->attempt_number ?? '',
                'Tipo Intento' => $item->attempt_type ? $this->getAttemptTypeLabel($item->attempt_type) : '',
                'Estado Cobro' => $item->charge_status ? $this->getChargeStatusLabel($item->charge_status) : '',
                'Razón Fallo' => $item->failure_reason ?? '',
                'Estado VirtualPos' => $item->virtualpos_status ?? '',
                'ID Cargo VirtualPos' => $item->virtualpos_charge_id ?? '',
                'ID Cargo Original' => $item->original_charge_id ?? '',
                'Fecha Intento' => $item->attempted_at ? Carbon::parse($item->attempted_at)->format('d-m-Y H:i') : '',
                'Fecha Resolución' => $item->resolved_at ? Carbon::parse($item->resolved_at)->format('d-m-Y H:i') : '',
                'Descripción Intento' => $item->description ?? '',

                // Información de la suscripción
                'Estado Suscripción' => $item->subscription_status,
                'ID Suscripción VirtualPos' => $item->virtualpos_subscription_id ?? '',
            ];
        });
    }

    /**
     * Formatear display completo del cargo
     */
    private function formatChargeDisplay($item): string
    {
        $status = $this->getChargeStatusLabel($item->charge_status);
        $attempt = $item->attempt_number;
        $amount = '$' . number_format($item->amount, 0, ',', '.');

        return "Intento #{$attempt} - {$status} - {$amount}";
    }

    /**
     * Formatear display de la cuota
     */
    private function formatInstallmentDisplay($item): ?string
    {
        if (!$item->installment_number) {
            return 'N/A';
        }

        $status = $item->installment_is_paid ? 'Pagada' : 'Pendiente';
        $source = $this->getPaymentSourceLabel($item->installment_payment_source);

        return "Cuota {$item->installment_number} - {$status}" . ($item->installment_is_paid ? " ({$source})" : '');
    }

    /**
     * Obtener etiqueta del estado del cargo
     */
    private function getChargeStatusLabel(?string $status): string
    {
        $labels = [
            'success' => 'Exitoso',
            'failed' => 'Fallido',
            'pending' => 'Pendiente',
            'cancelled' => 'Cancelado',
        ];

        return $labels[$status] ?? 'N/A';
    }

    /**
     * Obtener etiqueta del estado de la cuota
     */
    private function getInstallmentStatusLabel(?string $status): string
    {
        $labels = [
            'pending' => 'Pendiente',
            'overdue' => 'Vencida',
            'paid' => 'Pagada',
            'cancelled' => 'Cancelada',
        ];

        return $labels[$status] ?? 'N/A';
    }

    /**
     * Obtener etiqueta del tipo de intento
     */
    private function getAttemptTypeLabel(?string $type): string
    {
        $labels = [
            'automatic' => 'Automático',
            'manual' => 'Manual',
        ];

        return $labels[$type] ?? 'Desconocido';
    }

    /**
     * Obtener etiqueta de la fuente de pago
     */
    private function getPaymentSourceLabel(?string $paymentSource): string
    {
        if (!$paymentSource) {
            return '';
        }

        $labels = [
            'subscription' => 'Suscripción',
            'manual_cash' => 'Presencial (Efectivo)',
            'manual_transfer' => 'Presencial (Transferencia)',
            'manual_online' => 'Online Manual',
            'manual_check' => 'Presencial (Cheque)',
            'manual_other' => 'Manual (Otro)',
        ];

        return $labels[$paymentSource] ?? ucfirst(str_replace('_', ' ', $paymentSource));
    }

    /**
     * Convertir texto a Title Case
     */
    private function toTitleCase(string $text): string
    {
        if ($text === '') {
            return '';
        }
        $lower = mb_strtolower($text, 'UTF-8');
        return preg_replace_callback('/\b[\p{L}]/u', function ($m) {
            return mb_strtoupper($m[0], 'UTF-8');
        }, $lower);
    }
}
