<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sistema de Cuotas LAT90 - Habilitado
    |--------------------------------------------------------------------------
    |
    | Switch principal para habilitar/deshabilitar completamente el sistema
    | de cuotas mensuales LAT90.
    |
    | Para desactivar el sistema: cambiar a false
    | Para reactivar el sistema: cambiar a true
    |
    */

    'enabled' => env('INSTALLMENTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Cuotas
    |--------------------------------------------------------------------------
    */

    // Número máximo de cuotas permitidas
    'max_installments' => env('INSTALLMENTS_MAX', 24),

    // Número de cuotas por defecto
    'default_installments' => env('INSTALLMENTS_DEFAULT', 12),

    // Monto mínimo para habilitar cuotas
    'min_amount_for_installments' => env('INSTALLMENTS_MIN_AMOUNT', 50000),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Fechas de Vencimiento
    |--------------------------------------------------------------------------
    */

    // Día del mes para vencimiento de cuotas (1-28, null = mismo día de creación)
    'due_day_of_month' => env('INSTALLMENTS_DUE_DAY', 5),

    // Días de offset para la primera cuota desde hoy
    'first_installment_days_offset' => env('INSTALLMENTS_FIRST_OFFSET', 30),

    // Usar fecha final del programa como referencia
    'use_program_final_date' => env('INSTALLMENTS_USE_PROGRAM_DATE', true),

    /*
    |--------------------------------------------------------------------------
    | Sistema Legacy
    |--------------------------------------------------------------------------
    */

    // Usar sistema legacy de orders_detail (mantener por compatibilidad)
    'use_legacy_system' => env('INSTALLMENTS_USE_LEGACY', true),

    // Sincronizar ambos sistemas (nuevo y legacy)
    'sync_with_legacy' => env('INSTALLMENTS_SYNC_LEGACY', true),

    /*
    |--------------------------------------------------------------------------
    | Comandos Programados
    |--------------------------------------------------------------------------
    */

    // Habilitar comando que marca cuotas vencidas
    'enable_overdue_checker' => env('INSTALLMENTS_ENABLE_OVERDUE_CHECKER', true),

    // Habilitar procesamiento automático de pagos
    'enable_payment_processor' => env('INSTALLMENTS_ENABLE_PAYMENT_PROCESSOR', true),

    /*
    |--------------------------------------------------------------------------
    | Reestructuración de Planes
    |--------------------------------------------------------------------------
    */

    // Número máximo de cuotas al reestructurar
    'max_installments_on_restructure' => env('INSTALLMENTS_MAX_RESTRUCTURE', 24),

    // Permitir reducir número de cuotas
    'allow_reduce_installments' => env('INSTALLMENTS_ALLOW_REDUCE', false),

    /*
    |--------------------------------------------------------------------------
    | Eventos y Notificaciones
    |--------------------------------------------------------------------------
    */

    // Habilitar eventos del sistema de cuotas
    'enable_events' => env('INSTALLMENTS_ENABLE_EVENTS', true),

    // Habilitar notificaciones de cuotas vencidas
    'enable_overdue_notifications' => env('INSTALLMENTS_ENABLE_OVERDUE_NOTIF', false),

    /*
    |--------------------------------------------------------------------------
    | Opciones Avanzadas
    |--------------------------------------------------------------------------
    */

    // Permitir múltiples planes activos por participante/programa
    'allow_multiple_active_plans' => env('INSTALLMENTS_ALLOW_MULTIPLE', false),

    // Días de gracia antes de marcar como vencida
    'grace_days' => env('INSTALLMENTS_GRACE_DAYS', 0),

    // Redondeo de montos (null, 'up', 'down', 'nearest')
    'rounding_mode' => env('INSTALLMENTS_ROUNDING', null),

];
