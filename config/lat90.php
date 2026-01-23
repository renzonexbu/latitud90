<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración de Latitud 90
    |--------------------------------------------------------------------------
    |
    | Configuración centralizada para datos de la empresa, logos, contactos
    | y información que aparece en PDFs, emails y otros documentos.
    |
    */

    'company' => [
        'name' => 'Latitud 90',
        'legal_name' => 'Experiencias Educativas y Capacitaciones SpA',
        'rut' => '76.203.719-K',
        'address' => [
            'full' => 'Carlos Antúnez 1941, Providencia',
            'street' => 'Carlos Antúnez 1941',
            'commune' => 'Providencia',
            'city' => 'Santiago',
            'region' => 'Región Metropolitana',
        ],
        'phone' => '+56988071858',
        'email' => 'info@latitud90.cl',
        'website' => 'www.latitud90.com',
        'representative' => [
            'name' => 'Carolina Emhart García',
            'rut' => '13.670.825-2',
        ],
    ],

    'pdf' => [
        'logo' => [
            'header' => 'images/PDFS/Contracts/logo_lat90.png',
            'footer' => 'images/PDFS/Contracts/logo_footer.png',
            'divider' => 'images/PDFS/Contracts/page_divider.png',
            'signature' => 'images/PDFS/Contracts/firma.png',
        ],
        'fonts' => [
            'regular' => 'fonts/CenturyGothic.ttf',
            'bold' => 'fonts/CenturyGothic-Bold.ttf',
        ],
        'signature' => [
            'name' => 'Carmen Gutiérrez M.',
            'position' => 'Jefa área de recaudación',
        ],
    ],

    'email' => [
        'from' => [
            'address' => 'info@latitud90.cl',
            'name' => 'Latitud 90',
        ],
        'support' => [
            'phone' => '+56 9 1234 5678',
        ],
    ],
    

    /*
    |--------------------------------------------------------------------------
    | Configuración de Guardian (Apoderados)
    |--------------------------------------------------------------------------
    |
    | require_email_verification: Controla si los apoderados deben verificar
    | su email antes de poder usar su cuenta
    |   - true  = El apoderado debe verificar su email para iniciar sesión (RECOMENDADO)
    |   - false = El apoderado puede usar su cuenta sin verificar el email
    |
    | Por defecto: true (verificación obligatoria para mayor seguridad)
    |
    */
    'guardian' => [
        'require_email_verification' => env('GUARDIAN_REQUIRE_EMAIL_VERIFICATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Pagos
    |--------------------------------------------------------------------------
    |
    | use_virtualpos: Controla el ambiente de TODOS los métodos de pago
    |   - true  = Producción (VirtualPos para tarjetas + VirtualPos/Khipu para transferencias)
    |   - false = QA/Test (Transbank para tarjetas + Khipu nativo para transferencias)
    |
    */
    'payment' => [
        'use_virtualpos' => env('USE_VIRTUALPOS', true),

        /*
        |--------------------------------------------------------------------------
        | Configuración de Emails de Pago
        |--------------------------------------------------------------------------
        |
        | email_delay_minutes: Minutos de espera antes de enviar el email de confirmación
        |                      después de un pago exitoso. Esto permite que BSale genere
        |                      la boleta antes de que el usuario reciba el correo.
        |                      Default: 10 minutos
        |
        | email_max_attempts: Número máximo de intentos para enviar el email
        |                     antes de marcarlo como fallido.
        |                     Default: 5 intentos
        |
        */
        'email_delay_minutes' => env('PAYMENT_EMAIL_DELAY_MINUTES', 10),
        'email_max_attempts' => env('PAYMENT_EMAIL_MAX_ATTEMPTS', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Suscripciones VirtualPos
    |--------------------------------------------------------------------------
    |
    | subscription_env: Controla el ambiente de las suscripciones
    |   - 'sandbox'    = Ambiente de pruebas (sin transacciones reales)
    |   - 'production' = Ambiente de producción (transacciones reales)
    |
    */
    'subscriptions' => [
        'env' => env('VIRTUALPOS_SUBSCRIPTION_ENV', 'sandbox'),

        /*
        |--------------------------------------------------------------------------
        | Configuración de Reintentos Automáticos
        |--------------------------------------------------------------------------
        |
        | max_retry_attempts: Número máximo de reintentos automáticos para cargos rechazados
        |                     Nota: VirtualPos ya hace 3 reintentos internos antes de marcar como rechazado.
        |                     Estos son reintentos ADICIONALES después de que VirtualPos falla.
        | retry_delay_hours: Horas de espera entre reintentos (para evitar múltiples cobros el mismo día)
        | notify_after_all_retries: Notificar al usuario solo después de agotar todos los reintentos
        |
        */
        'retry' => [
            'enabled' => env('SUBSCRIPTION_RETRY_ENABLED', true),
            'max_attempts' => env('SUBSCRIPTION_MAX_RETRY_ATTEMPTS', 2),
            'delay_hours' => env('SUBSCRIPTION_RETRY_DELAY_HOURS', 24),
            'notify_after_all_retries' => env('SUBSCRIPTION_NOTIFY_AFTER_ALL_RETRIES', true),
        ],
    ],
];
