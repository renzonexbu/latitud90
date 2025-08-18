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
        'legal_name' => 'Experiencias Educativas y Capacitaciones',
        'address' => 'Carlos Antúnez 1941, Providencia',
        'region' => 'Región Metropolitana',
        'phone' => '+56 9 7909 1738',
        'email' => 'info@latitud90.cl',
        'website' => 'www.latitud90.com',
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
            'name' => 'Carmen Gutiérrez M',
            'position' => 'Área de recaudación',
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
];
