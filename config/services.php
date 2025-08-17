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
        'api_key' => env('KHIPU_API_KEY', 'ae940262-4f1a-4a0d-aef3-e5406b533b44'),
        'base_url' => env('KHIPU_BASE_URL', 'https://payment-api.khipu.com'),
    ],

];
