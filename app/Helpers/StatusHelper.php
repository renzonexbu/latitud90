<?php

namespace App\Helpers;

class StatusHelper
{
    /**
     * Traducir el estado de un participante a texto amigable
     *
     * @param string|null $status
     * @return string
     */
    public static function translateParticipantStatus(?string $status): string
    {
        return match($status) {
            'pending_payment' => 'Pendiente de Pago',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            default => ucfirst(str_replace('_', ' ', $status ?? 'N/A'))
        };
    }

    /**
     * Traducir el estado de un programa del participante a texto amigable
     *
     * @param string $status
     * @return string
     */
    public static function translateProgramStatus(string $status): string
    {
        return match($status) {
            'pending_payment' => 'Pendiente de Pago',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Liberado',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }

    /**
     * Traducir el estado de un pago a texto amigable
     *
     * @param string $status
     * @return string
     */
    public static function translatePaymentStatus(string $status): string
    {
        return match($status) {
            'pending' => 'Pendiente',
            'pending_payment' => 'Pendiente de Pago',
            'processing' => 'Procesando',
            'completed' => 'Completado',
            'approved' => 'Aprobado',
            'failed' => 'Fallido',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }

    /**
     * Traducir el estado de una orden a texto amigable
     *
     * @param string $status
     * @return string
     */
    public static function translateOrderStatus(string $status): string
    {
        return match($status) {
            'pending' => 'Pendiente',
            'processing' => 'Procesando',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            'refunded' => 'Reembolsado',
            default => ucfirst(str_replace('_', ' ', $status))
        };
    }
}
