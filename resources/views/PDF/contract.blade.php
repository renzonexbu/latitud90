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
        /* Carga embebida de Century Gothic si existe en resources/fonts */
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
            /* Reducimos margen superior; el banner va en el flujo */
        }

        .page-header {
            position: fixed;
            top: 10px;
            left: 1cm;
            height: 30px;
        }

        .header-logo {
            width: 1.5cm;
            height: 1.73cm;
        }

        /* Ocultar header en la primera página */
        @page :first {
            margin-top: 60px;
        }

        @page :not(:first) {
            margin-top: 40px;
        }

        @page :first .page-header {
            display: none !important;
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

        .brand {
            font-size: 14px;
            font-weight: bold;
        }

        .brand small {
            font-weight: normal;
            font-size: 11px;
            color: #666;
        }

        .muted {
            color: #666;
        }

        h1 {
            font-family: 'Calibri Light', Calibri, sans-serif;
            font-size: 11px;
            text-align: center;
            text-transform: uppercase;
            margin: 12px 0 4px 0;
        }

        .folio {
            text-align: right;
            font-weight: bold;
            margin-bottom: 14px;
            font-size: 11px;
            font-weight: bold;
        }

        h2 {
            font-family: 'Calibri Light', Calibri, sans-serif;
            font-size: 11px;
            font-weight: bold;
            margin: 18px 0 12px 0;
            text-transform: uppercase;
        }

        p {
            font-family: 'Calibri Light', Calibri, sans-serif;
            font-size: 11px;
            margin: 6px 0;
            text-align: justify;
        }

        ul,
        ol {
            font-family: 'Calibri Light', Calibri, sans-serif;
            font-size: 11px;
            margin: 6px 0 6px 18px;
        }

        .section {
            margin-bottom: 8px;
        }

        .signature-blocks {
            margin-top: 28px;
            width: 100%;
        }

        .signature {
            width: 100%;
            margin-top: 20px;
            text-align: center;
        }

        .sig-role {
            font-family: 'Calibri Light', Calibri, sans-serif;
            font-size: 11px;
            margin-top: 8px;
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



        .mb-4 {
            margin-bottom: 12px;
        }

        .mb-2 {
            margin-bottom: 6px;
        }

        .mt-2 {
            margin-top: 6px;
        }

        .mt-4 {
            margin-top: 12px;
        }

        .bold {
            font-weight: bold;
        }

        .center {
            text-align: center;
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
        $imgLogoLat = base64_encode(file_get_contents(resource_path(config('lat90.pdf.logo.header'))));
        $imgDivider = base64_encode(file_get_contents(resource_path(config('lat90.pdf.logo.divider'))));
        $imgLogoFooter = base64_encode(file_get_contents(resource_path(config('lat90.pdf.logo.footer'))));
        $imgFirma = base64_encode(file_get_contents(resource_path(config('lat90.pdf.logo.signature'))));
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
        <!-- Banner SOLO en la primera página (forma parte del contenido, no se repite) -->
        <div class="header-inner" style="margin-bottom: 8px;">
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
        <h1>CONTRATO DE RESERVA DE PROGRAMA EDUCATIVO</h1>
        <div class="folio">Folio {{ $folio ?? '2928-23047791' }}</div>
        <div class="section">
            <p>
                En {{ $ciudad ?? 'Santiago de Chile' }}, con fecha {{ $fecha ?? '30 de abril de 2025' }}, entre
                {{ $prestador_nombre ?? 'Experiencias Educativas y Capacitaciones SpA' }}, sociedad del giro de su
                denominación, Rol Único Tributario N° {{ $prestador_rut ?? '76.203.719-K' }}, representada por doña
                {{ $representante_nombre ?? 'Carolina Emhart García' }}, cédula nacional de identidad número
                {{ $representante_rut ?? '13.670.825-2' }}, ambos domiciliados en la ciudad de
                {{ $domicilio_ciudad ?? 'Santiago' }} y comuna de {{ $domicilio_comuna ?? 'Providencia' }},
                {{ $domicilio_calle ?? 'Carlos Antúnez 1941' }}, en adelante "El Prestador de servicios"; y por otra
                parte, don/a {{ $apoderado_nombre ?? 'N/A' }}, 
                cédula nacional de identidad número
                {{ $apoderado_rut ?? 'N/A' }} como apoderado(a) del alumno(a)
                {{ $alumno_nombre ?? 'N/A' }}, 
                @if(isset($alumno_tipo_documento) && strtolower($alumno_tipo_documento) === 'rut')
                    cédula nacional de identidad número
                @else
                    pasaporte
                @endif
                {{ $alumno_rut ?? 'N/A' }}, se ha convenido el presente contrato de Reserva de Programa
                Educativo.
            </p>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 33px;">PRIMERO. ANTECEDENTES</h2>
            <ol>
                <li>Experiencias Educativas y Capacitaciones SpA es una Empresa con trayectoria en el diseño y operación
                    de
                    viajes, eventos y programa de formación de personas y tienen como principal objetivo la generación y
                    prestación de servicios de programas educativos.</li>
                <li>El Apoderado corresponde al padre, madre o tutor legal del alumno, y que se encuentra interesado en
                    la
                    contratación de un Programa Educativo en beneficio del alumno.</li>
                <li>Por su parte el alumno es parte de una Institución Educativa que ha cotizado con el Prestador de
                    Servicios el Programa Educativo o en su defecto con un Grupo de Apoderados pertenecientes a una
                    Institución Educativa en particular, que ha cotizado con el Prestador de Servicios un Programa
                    Educativo.</li>
                <li>Que un encargado comercial del Prestador de Servicios hizo entrega al apoderado con fecha
                    {{ $cotizacion_fecha ?? '24 de marzo de 2025' }} Cotización de Programa Educativo a realizar
                    durante el
                    año {{ $programa_anio ?? '2026' }} denominado
                    {{ $programa_nombre ?? 'Full San Pedro de Atacama 2026' }}.</li>
            </ol>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 33px;">SEGUNDO. OBJETO DEL CONTRATO</h2>
            <p>El apoderado, ya singularizado, se encuentra interesado en contratar el Programa Educativo y viene a
                hacer pago de una Reserva para asegurar el cupo del alumno ya singularizado, el cual se puede hacer
                mediante abonos que se consideran anticipos.</p>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 33px;">TERCERO. PAGO</h2>
            <p>Los montos abonados se irán deduciendo del total adeudado, y la simple transferencia o el método de pago
                utilizado servirán como comprobante de pago.</p>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 33px;">CUARTO. DECLARACIONES DE LAS PARTES</h2>
            <p>Las partes declaran aceptar y conocer.</p>
            <ol>
                <li>Estar en conocimiento que la Cotización puede ser modificada por el grupo de apoderados o el
                    Prestador de
                    Servicios, lo que traerá aparejada una modificación en el precio final del Programa Educativo.</li>
                <li>Que el valor del Programa Educativo está basado en un mínimo de alumnos y consecuencialmente las
                    eventuales anulaciones pueden traer como consecuencia la cancelación del Programa de Estudios o el
                    aumento del precio por alumno.</li>
                <li>Que el Programa Educativo y la Cotización enviada aún se encuentran en periodo de negociación entre
                    las
                    partes, por lo que las fechas tentativas, itinerarios y lugares de destino pueden ser modificados
                    por
                    acuerdo de las partes singularizadas en la cláusula PRIMERA.</li>
                <li>Que una vez formalizada la Cotización, el cotizante firmará el Contrato de Condiciones Comerciales
                    respectivo.</li>
                <li>Que los pagos efectuados serán recaudados por el Prestador de Servicios, emitiendo la respectiva
                    boleta
                    una vez que se efectúe el servicio, a más tardar durante el año {{ $programa_anio ?? '2026' }}.
                </li>
                <li>En caso de anularse el viaje se harán efectivas las políticas de anulación:
                    <ol type="a">
                        <li>Anulación, devolución y/o cambios</li>
                        <li>Se retendrá como penalidad a todo evento el 17% del valor total del programa "por concepto
                            de
                            inscripción", para solventar gastos operativos y administrativos (no reembolsable).</li>
                        <li>Adicionalmente al 17% del valor total del programa, en viajes terrestres y/o aéreos desde 60
                            hasta
                            45 días antes de la salida, se retendrá un 20% del total, totalizando un 37% del valor del
                            Programa.</li>
                        <li>Adicionalmente al 37% del valor total del Programa mencionado en c., de 44 a 16 días antes
                            de la salida se
                            retendrá adicionalmente un 20% del total (En Viajes Aéreos o terrestres), totalizando un 57%
                            del
                            valor del programa.</li>
                        <li>Desde el día 15 hasta el día de salida se retendrá un 100% del total.</li>
                        <li>Los mayores gastos que se originen en un Programa, por disminución, prolongación, o
                            reprogramación en los
                            itinerarios inicialmente contratados, por causas no imputables al Prestador de Servicios,
                            tales como
                            condiciones meteorológicas, pandemia, medidas de la autoridad sanitaria, cortes de puentes,
                            pasos
                            fronterizos, catástrofe o estado de excepción, serán de cargo del Grupo que viaja. En este
                            caso La
                            Empresa no se obliga a compensación o devolución alguna. El Prestador de Servicios podrá
                            introducir
                            reprogramación y cambios en el Programa, en las rutas y horarios previamente establecidos
                            por razones de
                            fuerza mayor, o por acuerdo entre el responsable del grupo y el Prestador de Servicios.</li>
                        <li>El programa deberá cumplirse en los términos estipulados, sin embargo, podrá sufrir
                            modificaciones por
                            razones de fuerza mayor (condiciones meteorológicas, pandemia – normas sanitarias, cortes de
                            puentes,
                            pasos fronterizos, catástrofe o estado de excepción) por acuerdo entre el responsable del
                            grupo y el
                            Prestador de Servicios. Se hace presente que los horarios y los tiempos de viaje, tratándose
                            de
                            operaciones terrestres, son estimativos, ya que su exacto cumplimiento depende de múltiples
                            factores,
                            entre otros: clima, condiciones de las carreteras (camino en mal estado, desvíos por
                            arreglos en la
                            ruta), aduanas y condiciones generales de seguridad.</li>
                    </ol>
                </li>
            </ol>
        </div>

        <div class="section">
            <h2 style="margin-bottom: 33px;">QUINTO. MISCELÁNEOS</h2>
            <ol>
                <li>Ley y Domicilio: El presente contrato se rige por la Ley Chilena.</li>
                <li>Modificaciones: El Contrato sólo podrá ser modificado por mutuo acuerdo entre las partes.</li>
                <li>Ejemplares o Firma: El presente contrato podrá suscribirse de manera física o manuscrita, o bien, a
                    través de la firma de un ejemplar electrónico, mediante la utilización de firma electrónica, a la
                    cual las Partes reconocen la misma validez y la autenticidad que cualquier firma física, manuscrita
                    u otra análoga, siendo una manifestación de voluntad. La firma electrónica cumple con los más altos
                    estándares de seguridad del mercado. Una vez firmado el documento el mismo será compartido entre las
                    Partes de manera electrónica.</li>
                <li>Solución de Controversias: En caso de surgir una controversia o disputa relacionada con este
                    contrato, las partes se comprometen a resolverla mediante negociación directa y de buena fe. Las
                    partes tendrán un plazo de 15 días hábiles, contados desde la notificación de la controversia, para
                    llegar a un acuerdo. Si no se alcanza una solución en este período, cualquiera de las partes podrá
                    iniciar un proceso de arbitraje que se regula en el párrafo siguiente.
                    <p>Cualquier dificultad o controversia que se produzca entre los contratantes respecto de la
                        aplicación, interpretación, duración, validez o ejecución de este contrato o cualquier otro
                        motivo se resolverá mediante arbitraje con arreglo al Reglamento Procesal de Arbitraje (CAM) de
                        la Cámara de Comercio de Santiago (CCS), vigente al momento de solicitarla. Las partes confieren
                        poder especial irrevocable a la Cámara de Comercio de Santiago A.G. (CCS), para que, a petición
                        escrita de cualquiera de ellas, designe a un árbitro arbitrador en cuanto al procedimiento y de
                        derecho en cuanto al fallo, de entre los integrantes del cuerpo arbitral del CAM Santiago.</p>
                    <p>En contra de las resoluciones del árbitro no procederá recurso alguno. El árbitro queda
                        especialmente facultado para resolver todo asunto relacionado con su competencia y/o
                        jurisdicción.</p>
                    <p>No se entenderán comprendidas dentro del compromiso arbitral las acciones mercantiles de cobro de
                        títulos de crédito impagos, tales como cheques, pagarés, facturas, y letras de cambio; sea se
                        inicien por juicios ejecutivos, gestiones preparatorias, juicios sumarios, ordinarios o de
                        liquidación de empresa. En esos casos se deberá acudir ante los tribunales ordinarios de
                        dependientes de la Ilustrísima Corte de Apelaciones de Santiago, prorrogándoles de manera
                        irrevocable las partes para tal efecto la competencia.</p>
                </li>
            </ol>
        </div>

        <div class="section mt-4">
            <p class="center">Por, {{ $prestador_nombre_firma ?? 'Experiencias Educativas y Capacitaciones SpA' }}.</p>

            <div class="signature">
                <img src="data:image/png;base64,{{ $imgFirma }}" alt="Firma"
                    style="width:2.69cm; height:2.78cm; margin: 10px 0;" />
                <div class="sig-role">
                    {{ $firmante_nombre ?? 'Carmen Gutiérrez M.' }}<br />
                    {{ $firmante_cargo ?? 'Jefa área de recaudación' }}
                </div>
            </div>
        </div>
    </main>



</body>

</html>
