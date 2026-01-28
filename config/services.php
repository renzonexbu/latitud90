<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'transbank' => [
        // Webpay Plus Mall (por defecto, códigos de integración oficiales)
        'commerce_code' => env('TRANSBANK_COMMERCE_CODE', '597055555535'), // Mall Parent
        'child_commerce_code' => env('TRANSBANK_CHILD_COMMERCE_CODE', '597055555536'), // Mall Child 1
        'api_key' => env('TRANSBANK_API_KEY', '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C'),
        'environment' => env('TRANSBANK_ENVIRONMENT', 'integration'), // integration or production
        // Las URLs no son necesarias con el SDK oficial; se mantienen por compatibilidad
        'base_url' => env('TRANSBANK_BASE_URL', 'https://webpay3gint.transbank.cl'),
        'production_url' => env('TRANSBANK_PRODUCTION_URL', 'https://webpay3g.transbank.cl'),
    ],

    'khipu' => [
        // Credenciales de prueba (sandbox)
        'test' => [
            'api_key' => env('KHIPU_TEST_API_KEY', 'ae940262-4f1a-4a0d-aef3-e5406b533b44'),
            'secret_key' => env('KHIPU_TEST_SECRET_KEY', ''),
        ],
        // Credenciales de producción
        'production' => [
            'api_key' => env('KHIPU_PROD_API_KEY'),
            'secret_key' => env('KHIPU_PROD_SECRET_KEY'),
        ],
        'base_url' => env('KHIPU_BASE_URL', 'https://payment-api.khipu.com'),
    ],

    'bsale' => [
        // Kill switch global: poner en false para desactivar TODA generación de boletas
        'enabled' => env('BSALE_ENABLED', true),
        // Kill switch específico para suscripciones (PAT): desactivar boletas en cuotas de suscripción
        'subscription_enabled' => env('BSALE_SUBSCRIPTION_ENABLED', false),
        // Kill switch específico para pagos totales: desactivar boletas en pagos de contado/total
        'total_enabled' => env('BSALE_TOTAL_ENABLED', true),
        'token' => env('BSALE_TOKEN'),
        'base_url' => env('BSALE_BASE_URL', 'https://api.bsale.io/v1'),
        // IDs configurables por ambiente/cuenta
        'document_type_id' => env('BSALE_DOCUMENT_TYPE_ID', 3), // NOTA VENTA por defecto
        'price_list_id' => env('BSALE_PRICE_LIST_ID', 2), // Lista Base detectada en sandbox
        // Modo pruebas: invertir lógica de "mismo año"
        'invert_same_year_logic' => env('BSALE_INVERT_SAME_YEAR_LOGIC', false),
    ],

    'virtualpos' => [
        // Configuración para Subscriptions API v3 (módulo independiente)
        // El ambiente se controla con VIRTUALPOS_SUBSCRIPTION_ENV en .env (sandbox/production)
        'api_url' => env('VIRTUALPOS_SUBSCRIPTION_ENV', 'sandbox') === 'production'
            ? 'https://api.virtualpos.cl/v3'
            : 'https://api.virtualpos-sandbox.com/v3',
        'api_key' => env('VIRTUALPOS_SUBSCRIPTION_ENV', 'sandbox') === 'production'
            ? env('VIRTUALPOS_PROD_API_KEY')
            : env('VIRTUALPOS_SANDBOX_API_KEY'),
        'secret_key' => env('VIRTUALPOS_SUBSCRIPTION_ENV', 'sandbox') === 'production'
            ? env('VIRTUALPOS_PROD_SECRET_KEY')
            : env('VIRTUALPOS_SANDBOX_SECRET_KEY'),
        'merchant_code' => env('VIRTUALPOS_MERCHANT_CODE'),

        // Configuración existente de cuotas (legacy)
        'no_cuotes' => [
            'api_key' => env('VP_API_KEY_SC', 'd0f282-9c0c9d-ab74f7-965013-03232e'),
            'secret_key' => env('VP_SECRET_SC', '13d86e16bbb4d2d05d83a82112b0d310'),
            'commerce_code' => env('VIRTUALPOS_COMMERCE_CODE_NO_COUTES', '41760243'),
        ],
        '3_cuotes' => [
            'api_key' => env('VP_API_KEY_3CSI', 'cf3b17-b85239-a3a242-1aaa9d-b74cf0'),
            'secret_key' => env('VP_SECRET_3CSI', '352cf28ba166598afef7c93e246c0b04'),
            'commerce_code' => env('VIRTUALPOS_COMMERCE_CODE_3_COUTES', '41760252'),
        ],
        '6_cuotes' => [
            'api_key' => env('VP_API_KEY_6CSI', 'b70bfd-0865f4-13e6ba-ed1802-cacaad'),
            'secret_key' => env('VP_SECRET_6CSI', '426e5436b6733f9f4c4a3d460e641725'),
            'commerce_code' => env('VIRTUALPOS_COMMERCE_CODE_6_COUTES', '41760255'),
        ],
        '9_cuotes' => [
            'api_key' => env('VP_API_KEY_9CSI', '755aca-18366a-2e96a4-e24137-3c16f0'),
            'secret_key' => env('VP_SECRET_9CSI', '0a7990ada573a145957b6f7a0c530c12'),
            'commerce_code' => env('VIRTUALPOS_COMMERCE_CODE_9_COUTES', '41760255'),
        ],
        '12_cuotes' => [
            'api_key' => env('VP_API_KEY_12CSI', '2a75eb-4d3776-000183-7d449d-edd576'),
            'secret_key' => env('VP_SECRET_12CSI', 'cff9a217d5d6f2e2caeb79fa4f3a3497'),
            'commerce_code' => env('VIRTUALPOS_COMMERCE_CODE_12_COUTES', '41760262'),
        ],
        'international' => [
            'api_key' => env('VP_API_KEY_INTERNATIONAL', 'e66971-d09fbe-9372f6-d0e81f-9f2788'),
            'secret_key' => env('VP_SECRET_INTERNATIONAL', 'e63c06f334bf760f7f029ebe0cc5dfe6'),
            'commerce_code' => env('VIRTUALPOS_COMMERCE_CODE_INTERNATIONAL', '41760262'),
        ]
    ],

];
