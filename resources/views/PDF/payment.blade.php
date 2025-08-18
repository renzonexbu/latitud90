<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8" />
    <title>Contrato de Reserva de Programa Educativo</title>
    @php
        $cgRegularPath = resource_path('fonts/CenturyGothic.ttf');
        $cgBoldPath = resource_path('fonts/CenturyGothic-Bold.ttf');
        $cgReg = file_exists($cgRegularPath) ? base64_encode(file_get_contents($cgRegularPath)) : null;
        $cgBold = file_exists($cgBoldPath) ? base64_encode(file_get_contents($cgBoldPath)) : null;
    @endphp
    <style>
        @if (!empty($cgReg))
            @font-face {
                font-family: 'CenturyGothic';
                src: url('data:font/ttf;base64,{{ $cgReg }}') format('truetype');
                font-weight: normal;
                font-style: normal;
            }
        @endif

        @if (!empty($cgBold))
            @font-face {
                font-family: 'CenturyGothic';
                src: url('data:font/ttf;base64,{{ $cgBold }}') format('truetype');
                font-weight: bold;
                font-style: normal;
            }
        @endif

        @page {
            margin: 60px 3cm 80px 3cm;
        }

        body {
            font-family: 'Calibri Light', Calibri, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.35;
        }

        header {
            position: fixed;
            top: -10px;
            left: 0;
            right: 0;
            height: 0;
        }

        footer {
            position: fixed;
            bottom: -50px;
            left: 0;
            right: 0;
            height: 50px;
            font-size: 11px;
            color: #666;
        }

        .footer-inner {
            width: 100%;
            text-align: left;
            padding-top: 10px;
        }

        .section {
            margin-bottom: 8px;
        }

        /* Header layout */
        .header-inner {
            width: 100%;
            display: table;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }

        .header-right {
            text-align: right;
        }

        .logo-lat90 {
            width: 2.34cm;
            height: 2.7cm;
        }

        .page-divider {
            width: 130px;
            display: inline-block;
            margin-top: 6px;
        }

        .company-data {
            font-size: 8pt;
            color: #0f6c7a;
            line-height: 1.3;
        }
    </style>
</head>

<body>
    @php
        $imgLogoLat = base64_encode(file_get_contents(resource_path('images/PDFS/Contracts/logo_lat90.png')));
        $imgDivider = base64_encode(file_get_contents(resource_path('images/PDFS/Contracts/page_divider.png')));
        $imgLogoFooter = base64_encode(file_get_contents(resource_path('images/PDFS/Contracts/logo_footer.png')));
        $imgFirma = base64_encode(file_get_contents(resource_path('images/PDFS/Contracts/firma.png')));
    @endphp
    <header></header>

    <!-- Header para páginas 2 en adelante -->
    {{-- <div class="page-header">
        <img class="header-logo" src="data:image/png;base64,{{ $imgLogoLat }}" alt="Lat90" />
    </div> --}}

    <footer>
        <div class="footer-inner">
            <img src="data:image/png;base64,{{ $imgLogoFooter }}" alt="Lat90" style="width:1.02cm; height:1.34cm;" />
        </div>
    </footer>

    <main>
        <div class="header-inner" style="margin-bottom: 50px;">
            <div class="header-left">
                <img class="logo-lat90" src="data:image/png;base64,{{ $imgLogoLat }}" alt="Lat90" />
            </div>
            <div class="header-right">
                <!-- Empujar el divider al borde superior de la hoja (igual al margin-top de @page: 60px) -->
                <img class="page-divider" src="data:image/png;base64,{{ $imgDivider }}" alt="divider"
                    style="display:block; margin:-60px 0 6px auto;" />
                <div class="company-data">
                    {{ $empresa_direccion ?? 'Carlos Antúnez 1941, Providencia' }}<br />
                    {{ $empresa_region ?? 'Región Metropolitana' }}<br />
                    {{ $empresa_telefono ?? '+56 9 7909 1738' }}<br />
                    {{ $empresa_sitio ?? 'www.latitud90.com' }}
                </div>
            </div>
        </div>
        <h1
            style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 16px; color: #307F92; text-align: center;">
            ANTICIPO CLIENTE RESERVA PROGRAMA Nro. {{ $folio ?? '2928' }}</h1>
        <div class="section">
            <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
                Hemos recibido de: {{ $apoderado_nombre ?? 'Juan Pérez' }}, la cantidad de: ${{ $monto ?? '100.000' }}
                pesos chilenos,
                por concepto de abono a contrato de reserva del programa educativo, folio:
                {{ $folio ?? '2959-22947415' }} de
                fecha; {{ $fecha ?? '01 de mayo, 2025' }} para alumno (a) {{ $alumno_nombre ?? 'María Pérez' }},
                cédula
                nacional de identidad Nro. {{ $alumno_rut ?? '22.947.415-4' }}
            </p>
        </div>

        <div class="section">
            <h2
                style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; text-align: center; text-decoration: underline; text-transform: none; margin-bottom: 30px; color: #222;">
                ESTADO de cuenta</h2>
            <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">Valor
                programa
                educativo, según cotización: ${{ $valor_programa ?? '1.419.000' }}</p>
            <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">Destino:
                {{ $destino ?? 'San Pedro de Atacama' }}</p>
            <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">Fecha:
                {{ $fecha_programa ?? 'Noviembre, 2026' }}</p>
        </div>

        <div class="section">
            <table
                style="width: 100%; border-collapse: collapse; margin-top: 20px; text-align: center; font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
                <thead>
                    <tr>
                        <th style="padding: 8px; font-weight: bold; color: #222;">Abono</th>
                        <th style="padding: 8px; font-weight: bold; color: #222;">Fecha</th>
                        <th style="padding: 8px; font-weight: bold; color: #222;">Saldo abonado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-top: 1px solid black;">
                        <td style="padding: 8px; color: #222;">${{ $monto_abono ?? '100.000' }}</td>
                        <td style="padding: 8px; color: #222;">{{ $fecha_abono ?? '01-05-2025' }}</td>
                        <td style="padding: 8px; color: #222;">${{ $saldo_abonado ?? '100.000' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section" style="margin-top: 50px;">
            <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">Por,
                {{ $prestador_nombre_firma ?? 'Experiencias Educativas y Capacitaciones' }}.</p>
            <p
                style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; margin-top: 100px; color: #222;">
                {{ $firmante_nombre ?? 'Carmen Gutiérrez M' }}<br />
                {{ $firmante_cargo ?? 'Área de recaudación' }}</p>
        </div>
    </main>



</body>

</html>
