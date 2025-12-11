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
    | Configuración de Pagos
    |--------------------------------------------------------------------------
    |
    | use_virtualpos: Controla el ambiente de TODOS los métodos de pago
    |   - true  = Producción (VirtualPos para tarjetas + VirtualPos/Khipu para transferencias)
    |   - false = QA/Test (Transbank para tarjetas + Khipu nativo para transferencias)
    |
    */
    'payment' => [
        'use_virtualpos' => env('USE_VIRTUALPOS', false),
    ],
];
