<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\DocumentTemplate;

class DocumentTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Plantilla de Contrato de Reserva
        $contractContent = <<<'HTML'
<div class="header-inner" style="margin-bottom: 8px;">
    <div class="header-left">
        <img class="logo-lat90" src="data:image/png;base64,{{logo_base64}}" alt="Lat90" />
    </div>
    <div class="header-right">
        <img class="page-divider" src="data:image/png;base64,{{divider_base64}}" alt="divider" style="display:block; margin:-60px 0 6px auto;" />
        <div class="company-data">
            {{empresa_direccion}}<br />
            {{empresa_region}}<br />
            {{empresa_telefono}}<br />
            {{empresa_sitio}}
        </div>
    </div>
</div>
<h1>CONTRATO DE RESERVA DE PROGRAMA EDUCATIVO</h1>
<div class="folio">Folio {{folio}}</div>
<div class="section">
    <p>
        En {{ciudad}}, con fecha {{fecha}}, entre {{prestador_nombre}}, sociedad del giro de su denominación, Rol Único Tributario N° {{prestador_rut}}, representada por doña {{representante_nombre}}, cédula nacional de identidad número {{representante_rut}}, ambos domiciliados en la ciudad de {{domicilio_ciudad}} y comuna de {{domicilio_comuna}}, {{domicilio_calle}}, en adelante "El Prestador de servicios"; y por otra parte, don/a {{apoderado_nombre}}, cédula nacional de identidad número {{apoderado_rut}} como apoderado(a) del alumno(a) {{alumno_nombre}}, {{alumno_documento_label}} {{alumno_rut}}, se ha convenido el presente contrato de Reserva de Programa Educativo.
    </p>
</div>

<div class="section">
    <h2 style="margin-bottom: 33px;">PRIMERO. ANTECEDENTES</h2>
    <ol>
        <li>Experiencias Educativas y Capacitaciones SpA es una Empresa con trayectoria en el diseño y operación de viajes, eventos y programa de formación de personas y tienen como principal objetivo la generación y prestación de servicios de programas educativos.</li>
        <li>El Apoderado corresponde al padre, madre o tutor legal del alumno, y que se encuentra interesado en la contratación de un Programa Educativo en beneficio del alumno.</li>
        <li>Por su parte el alumno es parte de una Institución Educativa que ha cotizado con el Prestador de Servicios el Programa Educativo o en su defecto con un Grupo de Apoderados pertenecientes a una Institución Educativa en particular, que ha cotizado con el Prestador de Servicios un Programa Educativo.</li>
        <li>Que un encargado comercial del Prestador de Servicios hizo entrega al apoderado con fecha {{cotizacion_fecha}} Cotización de Programa Educativo a realizar durante el año {{programa_anio}} denominado {{programa_nombre}}.</li>
    </ol>
</div>

<div class="section">
    <h2 style="margin-bottom: 33px;">SEGUNDO. OBJETO DEL CONTRATO</h2>
    <p>El apoderado, ya singularizado, se encuentra interesado en contratar el Programa Educativo y viene a hacer pago de una Reserva para asegurar el cupo del alumno ya singularizado, el cual se puede hacer mediante abonos que se consideran anticipos.</p>
</div>

<div class="section">
    <h2 style="margin-bottom: 33px;">TERCERO. PAGO</h2>
    <p>Los montos abonados se irán deduciendo del total adeudado, y la simple transferencia o el método de pago utilizado servirán como comprobante de pago.</p>
</div>

<div class="section">
    <h2 style="margin-bottom: 33px;">CUARTO. DECLARACIONES DE LAS PARTES</h2>
    <p>Las partes declaran aceptar y conocer.</p>
    <ol>
        <li>Estar en conocimiento que la Cotización puede ser modificada por el grupo de apoderados o el Prestador de Servicios, lo que traerá aparejada una modificación en el precio final del Programa Educativo.</li>
        <li>Que el valor del Programa Educativo está basado en un mínimo de alumnos y consecuencialmente las eventuales anulaciones pueden traer como consecuencia la cancelación del Programa de Estudios o el aumento del precio por alumno.</li>
        <li>Que el Programa Educativo y la Cotización enviada aún se encuentran en periodo de negociación entre las partes, por lo que las fechas tentativas, itinerarios y lugares de destino pueden ser modificados por acuerdo de las partes singularizadas en la cláusula PRIMERA.</li>
        <li>Que una vez formalizada la Cotización, el cotizante firmará el Contrato de Condiciones Comerciales respectivo.</li>
        <li>Que los pagos efectuados serán recaudados por el Prestador de Servicios, emitiendo la respectiva boleta una vez que se efectúe el servicio, a más tardar durante el año {{programa_anio}}.</li>
        <li>En caso de anularse el viaje se harán efectivas las políticas de anulación:
            <ol type="a">
                <li>Anulación, devolución y/o cambios</li>
                <li>Se retendrá como penalidad a todo evento el 17% del valor total del programa "por concepto de inscripción", para solventar gastos operativos y administrativos (no reembolsable).</li>
                <li>Adicionalmente al 17% del valor total del programa, en viajes terrestres y/o aéreos desde 60 hasta 45 días antes de la salida, se retendrá un 20% del total, totalizando un 37% del valor del Programa.</li>
                <li>Adicionalmente al 37% del valor total del Programa mencionado en c., de 44 a 16 días antes de la salida se retendrá adicionalmente un 20% del total (En Viajes Aéreos o terrestres), totalizando un 57% del valor del programa.</li>
                <li>Desde el día 15 hasta el día de salida se retendrá un 100% del total.</li>
                <li>Los mayores gastos que se originen en un Programa, por disminución, prolongación, o reprogramación en los itinerarios inicialmente contratados, por causas no imputables al Prestador de Servicios, tales como condiciones meteorológicas, pandemia, medidas de la autoridad sanitaria, cortes de puentes, pasos fronterizos, catástrofe o estado de excepción, serán de cargo del Grupo que viaja. En este caso La Empresa no se obliga a compensación o devolución alguna. El Prestador de Servicios podrá introducir reprogramación y cambios en el Programa, en las rutas y horarios previamente establecidos por razones de fuerza mayor, o por acuerdo entre el responsable del grupo y el Prestador de Servicios.</li>
                <li>El programa deberá cumplirse en los términos estipulados, sin embargo, podrá sufrir modificaciones por razones de fuerza mayor (condiciones meteorológicas, pandemia – normas sanitarias, cortes de puentes, pasos fronterizos, catástrofe o estado de excepción) por acuerdo entre el responsable del grupo y el Prestador de Servicios. Se hace presente que los horarios y los tiempos de viaje, tratándose de operaciones terrestres, son estimativos, ya que su exacto cumplimiento depende de múltiples factores, entre otros: clima, condiciones de las carreteras (camino en mal estado, desvíos por arreglos en la ruta), aduanas y condiciones generales de seguridad.</li>
            </ol>
        </li>
    </ol>
</div>

<div class="section">
    <h2 style="margin-bottom: 33px;">QUINTO. MISCELÁNEOS</h2>
    <ol>
        <li>Ley y Domicilio: El presente contrato se rige por la Ley Chilena.</li>
        <li>Modificaciones: El Contrato sólo podrá ser modificado por mutuo acuerdo entre las partes.</li>
        <li>Ejemplares o Firma: El presente contrato podrá suscribirse de manera física o manuscrita, o bien, a través de la firma de un ejemplar electrónico, mediante la utilización de firma electrónica, a la cual las Partes reconocen la misma validez y la autenticidad que cualquier firma física, manuscrita u otra análoga, siendo una manifestación de voluntad. La firma electrónica cumple con los más altos estándares de seguridad del mercado. Una vez firmado el documento el mismo será compartido entre las Partes de manera electrónica.</li>
        <li>Solución de Controversias: En caso de surgir una controversia o disputa relacionada con este contrato, las partes se comprometen a resolverla mediante negociación directa y de buena fe. Las partes tendrán un plazo de 15 días hábiles, contados desde la notificación de la controversia, para llegar a un acuerdo. Si no se alcanza una solución en este período, cualquiera de las partes podrá iniciar un proceso de arbitraje que se regula en el párrafo siguiente.
            <p>Cualquier dificultad o controversia que se produzca entre los contratantes respecto de la aplicación, interpretación, duración, validez o ejecución de este contrato o cualquier otro motivo se resolverá mediante arbitraje con arreglo al Reglamento Procesal de Arbitraje (CAM) de la Cámara de Comercio de Santiago (CCS), vigente al momento de solicitarla. Las partes confieren poder especial irrevocable a la Cámara de Comercio de Santiago A.G. (CCS), para que, a petición escrita de cualquiera de ellas, designe a un árbitro arbitrador en cuanto al procedimiento y de derecho en cuanto al fallo, de entre los integrantes del cuerpo arbitral del CAM Santiago.</p>
            <p>En contra de las resoluciones del árbitro no procederá recurso alguno. El árbitro queda especialmente facultado para resolver todo asunto relacionado con su competencia y/o jurisdicción.</p>
            <p>No se entenderán comprendidas dentro del compromiso arbitral las acciones mercantiles de cobro de títulos de crédito impagos, tales como cheques, pagarés, facturas, y letras de cambio; sea se inicien por juicios ejecutivos, gestiones preparatorias, juicios sumarios, ordinarios o de liquidación de empresa. En esos casos se deberá acudir ante los tribunales ordinarios de dependientes de la Ilustrísima Corte de Apelaciones de Santiago, prorrogándoles de manera irrevocable las partes para tal efecto la competencia.</p>
        </li>
    </ol>
</div>

<div class="section mt-4">
    <p class="center">Por, {{prestador_nombre_firma}}.</p>
    <div class="signature">
        <img src="data:image/png;base64,{{firma_base64}}" alt="Firma" style="width:2.69cm; height:2.78cm; margin: 10px 0;" />
        <div class="sig-role">
            {{firmante_nombre}}<br />
            {{firmante_cargo}}
        </div>
    </div>
</div>
HTML;

        // Plantilla de Comprobante de Pago
        $paymentReceiptContent = <<<'HTML'
<div class="header-inner" style="margin-bottom: 50px;">
    <div class="header-left">
        <img class="logo-lat90" src="data:image/png;base64,{{logo_base64}}" alt="Lat90" />
    </div>
    <div class="header-right">
        <img class="page-divider" src="data:image/png;base64,{{divider_base64}}" alt="divider" style="display:block; margin:-60px 0 6px auto;" />
        <div class="company-data">
            {{empresa_direccion}}<br />
            {{empresa_region}}<br />
            {{empresa_telefono}}<br />
            {{empresa_sitio}}
        </div>
    </div>
</div>
<h1 style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 16px; color: #307F92; text-align: center;">
    ANTICIPO CLIENTE RESERVA PROGRAMA Nro. {{folio}}
</h1>
<div class="section">
    <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
        Hemos recibido de: {{apoderado_nombre}}, la cantidad de: ${{monto}} pesos chilenos, por concepto de abono a contrato de reserva del programa educativo, folio: {{folio}} de fecha; {{fecha}} para alumno (a) {{alumno_nombre}}, {{document_type}} {{alumno_rut}}
    </p>
</div>

<div class="section">
    <h2 style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; text-align: center; text-decoration: underline; text-transform: none; margin-bottom: 30px; color: #222;">
        Estado de cuenta
    </h2>
    <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
        Valor programa educativo, según cotización: ${{valor_programa}}
    </p>
    <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
        Destino: {{destino}}
    </p>
    <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
        Fecha: {{fecha_programa}}
    </p>
</div>

<div class="section">
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px; text-align: center; font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
        <thead>
            <tr>
                <th style="padding: 8px; font-weight: bold; color: #222;">Abono</th>
                <th style="padding: 8px; font-weight: bold; color: #222;">Fecha</th>
                <th style="padding: 8px; font-weight: bold; color: #222;">Saldo abonado</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-top: 1px solid black;">
                <td style="padding: 8px; color: #222;">${{monto_abono}}</td>
                <td style="padding: 8px; color: #222;">{{fecha_abono}}</td>
                <td style="padding: 8px; color: #222;">${{saldo_abonado}}</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="section" style="margin-top: 50px;">
    <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; color: #222;">
        Por, {{prestador_nombre_firma}}.
    </p>
    <p style="font-family: 'CenturyGothic', Century Gothic, sans-serif; font-size: 14px; margin-top: 100px; color: #222;">
        {{firmante_nombre}}<br />
        {{firmante_cargo}}
    </p>
</div>
HTML;

        // Variables disponibles para el contrato
        $contractVariables = [
            'logo_base64' => 'Logo de la empresa en base64',
            'divider_base64' => 'Divisor en base64',
            'firma_base64' => 'Firma en base64',
            'empresa_direccion' => 'Dirección de la empresa',
            'empresa_region' => 'Región',
            'empresa_telefono' => 'Teléfono',
            'empresa_sitio' => 'Sitio web',
            'folio' => 'Folio del contrato',
            'ciudad' => 'Ciudad',
            'fecha' => 'Fecha del contrato',
            'prestador_nombre' => 'Nombre del prestador',
            'prestador_rut' => 'RUT del prestador',
            'representante_nombre' => 'Nombre del representante',
            'representante_rut' => 'RUT del representante',
            'domicilio_ciudad' => 'Ciudad de domicilio',
            'domicilio_comuna' => 'Comuna de domicilio',
            'domicilio_calle' => 'Calle de domicilio',
            'apoderado_nombre' => 'Nombre del apoderado',
            'apoderado_rut' => 'RUT del apoderado',
            'alumno_nombre' => 'Nombre del alumno',
            'alumno_documento_label' => 'Etiqueta del documento del alumno',
            'alumno_rut' => 'Documento del alumno',
            'cotizacion_fecha' => 'Fecha de cotización',
            'programa_anio' => 'Año del programa',
            'programa_nombre' => 'Nombre del programa',
            'prestador_nombre_firma' => 'Nombre para la firma',
            'firmante_nombre' => 'Nombre del firmante',
            'firmante_cargo' => 'Cargo del firmante',
        ];

        // Variables disponibles para el comprobante de pago
        $paymentReceiptVariables = [
            'logo_base64' => 'Logo de la empresa en base64',
            'divider_base64' => 'Divisor en base64',
            'empresa_direccion' => 'Dirección de la empresa',
            'empresa_region' => 'Región',
            'empresa_telefono' => 'Teléfono',
            'empresa_sitio' => 'Sitio web',
            'folio' => 'Folio del comprobante',
            'apoderado_nombre' => 'Nombre del apoderado',
            'monto' => 'Monto del pago',
            'fecha' => 'Fecha del pago',
            'alumno_nombre' => 'Nombre del alumno',
            'document_type' => 'Tipo de documento',
            'alumno_rut' => 'Documento del alumno',
            'valor_programa' => 'Valor del programa',
            'destino' => 'Destino del programa',
            'fecha_programa' => 'Fecha del programa',
            'monto_abono' => 'Monto del abono',
            'fecha_abono' => 'Fecha del abono',
            'saldo_abonado' => 'Saldo abonado',
            'prestador_nombre_firma' => 'Nombre para la firma',
            'firmante_nombre' => 'Nombre del firmante',
            'firmante_cargo' => 'Cargo del firmante',
        ];

        // Crear plantilla de contrato
        DocumentTemplate::create([
            'type' => DocumentTemplate::TYPE_CONTRACT,
            'name' => 'Contrato de Reserva de Programa Educativo',
            'content' => $contractContent,
            'variables' => $contractVariables,
            'is_active' => true,
            'version' => 1,
            'notes' => 'Plantilla inicial del contrato de reserva',
        ]);

        // Crear plantilla de comprobante de pago
        DocumentTemplate::create([
            'type' => DocumentTemplate::TYPE_PAYMENT_RECEIPT,
            'name' => 'Comprobante de Pago',
            'content' => $paymentReceiptContent,
            'variables' => $paymentReceiptVariables,
            'is_active' => true,
            'version' => 1,
            'notes' => 'Plantilla inicial del comprobante de pago',
        ]);

        // Plantilla de Evidencia de Aceptación de Términos y Condiciones
        $termsAcceptanceContent = <<<'HTML'
<div class="header-inner" style="margin-bottom: 25px;">
    <div class="header-left">
        <img class="logo-lat90" src="data:image/png;base64,{{logo_base64}}" alt="Lat90" />
    </div>
    <div class="header-right">
        <img class="page-divider" src="data:image/png;base64,{{divider_base64}}" alt="divider" style="display:block; margin:-60px 0 6px auto;" />
        <div class="company-data">
            {{empresa_direccion}}<br />
            {{empresa_region}}<br />
            {{empresa_telefono}}<br />
            {{empresa_sitio}}
        </div>
    </div>
</div>

<div style="margin-bottom: 20px;">
    <p style="font-family: 'Calibri', sans-serif; font-size: 14px; font-weight: bold; color: #0f6c7a; margin: 0;">
        Evidencia de Aceptación de Términos y Condiciones
    </p>
</div>

<div class="section">
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; font-weight: bold; color: #222; margin: 0 0 5px 0;">
        Identificación del Usuario
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Nombre:</strong> {{usuario_nombre}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>{{documento_tipo}}:</strong> {{documento_numero}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Correo electrónico:</strong> {{usuario_email}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Usuario del sistema:</strong> {{usuario_email}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Programa:</strong> {{programa}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Método de autenticación:</strong> Inicio de sesión mediante usuario y contraseña
    </p>
</div>

<div class="section" style="margin-top: 15px;">
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; font-weight: bold; color: #222; margin: 0 0 5px 0;">
        Descripción del Proceso de Aceptación
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; text-align: justify; margin: 2px 0; padding-left: 15px;">
        El sistema implementado para la aceptación de los Términos y Condiciones requiere la manifestación expresa e inequívoca del usuario.
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; text-align: justify; margin: 2px 0; padding-left: 15px;">
        Antes de iniciar el proceso de introducción de datos de pago, el usuario debe marcar un checkbox indicando que ha leído y acepta los Términos y Condiciones vigentes.
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; text-align: justify; margin: 2px 0; padding-left: 15px;">
        El sistema no permite continuar con el flujo de contratación si el checkbox no ha sido marcado. Este mecanismo constituye una acción afirmativa verificable y registrada en el sistema.
    </p>
</div>

<div class="section" style="margin-top: 15px;">
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; font-weight: bold; color: #222; margin: 0 0 5px 0;">
        Registro del Evento de Aceptación (Log)
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Evento:</strong> Aceptación de Términos y Condiciones
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>ID de evento:</strong> {{evento_id}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Usuario:</strong> {{usuario_email}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Nombre asociado:</strong> {{usuario_nombre}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Correo asociado:</strong> {{usuario_email}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Fecha y hora:</strong> {{fecha_aceptacion}} (UTC-3)
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Dirección IP:</strong> {{ip_address}}
    </p>
    <p style="font-family: 'Calibri', sans-serif; font-size: 11px; color: #222; margin: 2px 0; padding-left: 15px;">
        <strong>Versión de Términos y Condiciones aceptada:</strong> {{tc_version}}
    </p>
</div>

<div class="section" style="margin-top: 20px;">
    <p style="font-family: 'Calibri', sans-serif; font-size: 10px; color: #555; text-align: justify;">
        El sistema registra automáticamente el evento de aceptación en la bitácora interna (log), asociándolo al usuario autenticado, su IP, la fecha exacta y la versión del documento aceptado. Estos registros son generados de forma automática y quedan almacenados en un entorno seguro, garantizando su integridad y trazabilidad.
    </p>
</div>
HTML;

        // Variables disponibles para evidencia de aceptación T&C
        $termsAcceptanceVariables = [
            'logo_base64' => 'Logo de la empresa en base64',
            'divider_base64' => 'Divisor en base64',
            'empresa_direccion' => 'Dirección de la empresa',
            'empresa_region' => 'Región',
            'empresa_telefono' => 'Teléfono',
            'empresa_sitio' => 'Sitio web',
            'usuario_nombre' => 'Nombre del usuario que aceptó',
            'documento_tipo' => 'Tipo de documento (RUT, Pasaporte, etc.)',
            'documento_numero' => 'Número de documento formateado',
            'usuario_email' => 'Correo electrónico del usuario',
            'programa' => 'Código y nombre del programa',
            'evento_id' => 'ID del evento de aceptación',
            'fecha_aceptacion' => 'Fecha y hora de aceptación',
            'ip_address' => 'Dirección IP desde donde se aceptó',
            'tc_version' => 'Versión de los T&C aceptados',
        ];

        // Crear plantilla de evidencia de aceptación T&C
        DocumentTemplate::create([
            'type' => DocumentTemplate::TYPE_TERMS_ACCEPTANCE_EVIDENCE,
            'name' => 'Evidencia de Aceptación de Términos y Condiciones',
            'content' => $termsAcceptanceContent,
            'variables' => $termsAcceptanceVariables,
            'is_active' => true,
            'version' => 1,
            'notes' => 'Plantilla inicial de evidencia de aceptación de T&C',
        ]);
    }
}
