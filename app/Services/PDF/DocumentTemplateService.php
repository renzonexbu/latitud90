<?php

namespace App\Services\PDF;

use App\Models\DocumentTemplate;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Traits\SystemLogging;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentTemplateService
{
    use SystemLogging;

    /**
     * Generar PDF desde una plantilla de la base de datos
     */
    public function generatePdfFromTemplate(string $type, OrderDetail $orderDetail, Payment $payment): string
    {
        try {
            // Obtener la plantilla activa del tipo especificado
            $template = DocumentTemplate::getActiveByType($type);

            if (!$template) {
                throw new \Exception("No se encontró una plantilla activa para el tipo: {$type}");
            }

            // Preparar los datos según el tipo
            $data = $this->prepareData($type, $orderDetail, $payment);

            // Renderizar el contenido con los datos
            $content = $template->render($data);

            // Construir el HTML completo con estilos
            $fullHtml = $this->buildFullHtml($content, $type);

            // Generar el PDF
            $pdf = Pdf::loadHTML($fullHtml);

            // Generar nombre único para el archivo
            $filename = $this->generateFilename($type, $orderDetail, $payment);

            // Guardar temporalmente el PDF
            $tempPath = storage_path('app/temp/' . $filename);

            // Asegurar que el directorio existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            $pdf->save($tempPath);

            $this->logInfo('DocumentTemplateService: PDF generado exitosamente', [
                'type' => $type,
                'template_id' => $template->id,
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'filename' => $filename,
            ]);

            return $tempPath;
        } catch (\Exception $e) {
            $this->logError('DocumentTemplateService: Error generando PDF', [
                'type' => $type,
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Preparar datos para reemplazar en la plantilla
     */
    private function prepareData(string $type, OrderDetail $orderDetail, Payment $payment): array
    {
        $program = $orderDetail->order->program;
        $participant = $orderDetail->order->participant;

        // Datos comunes a todos los tipos de documentos
        $commonData = [
            'logo_base64' => $this->getImageBase64(config('lat90.pdf.logo.header')),
            'divider_base64' => $this->getImageBase64(config('lat90.pdf.logo.divider')),
            'firma_base64' => $this->getImageBase64(config('lat90.pdf.logo.signature')),
            'empresa_direccion' => config('lat90.company.address.full', 'Carlos Antúnez 1941, Providencia'),
            'empresa_region' => config('lat90.company.address.region', 'Región Metropolitana'),
            'empresa_telefono' => config('lat90.company.phone', '+56 9 7909 1738'),
            'empresa_sitio' => config('lat90.company.website', 'www.latitud90.com'),
        ];

        if ($type === DocumentTemplate::TYPE_CONTRACT) {
            return array_merge($commonData, $this->prepareContractData($orderDetail, $payment, $program, $participant));
        }

        // TYPE_PAYMENT_RECEIPT
        return array_merge($commonData, $this->preparePaymentReceiptData($orderDetail, $payment, $program, $participant));
    }

    /**
     * Preparar datos específicos para el contrato
     */
    private function prepareContractData(OrderDetail $orderDetail, Payment $payment, $program, $participant): array
    {
        // Obtener el tipo de documento del participante
        $documentType = $participant->documentType;
        $documentTypeName = $documentType ? $documentType->name : 'N/A';

        // Formatear número de documento del participante
        $participantDocumentNumber = $this->formatDocumentNumber($participant->document_number, $documentTypeName);

        // Obtener el contacto de emergencia como apoderado
        $emergencyContact = $participant->emergencyContacts()->first();
        $apoderadoNombre = $emergencyContact ? $this->capitalizeWords($emergencyContact->name) : 'N/A';
        $apoderadoDocument = $emergencyContact ? $this->formatDocumentNumber($emergencyContact->document_number, 'RUT') : 'N/A';

        // Generar folio del contrato
        $programCode = $program->code ?? 'PROG';
        $cleanDocument = '';

        if (strtolower($documentTypeName) === 'rut') {
            $cleanDocument = preg_replace('/[.-]/', '', $participant->document_number);
            $cleanDocument = substr($cleanDocument, 0, -1);
        } else {
            $cleanDocument = preg_replace('/[.-]/', '', $participant->document_number);
        }

        $folio = $programCode . '-' . $cleanDocument;

        // Construir nombre completo del participante
        $participantFullName = $participant->full_name;

        // Determinar etiqueta del documento
        $alumnoDocumentoLabel = strtolower($documentTypeName) === 'rut'
            ? 'cédula nacional de identidad número'
            : 'pasaporte';

        return [
            'folio' => $folio,
            'fecha' => $payment->created_at->format('d \d\e F \d\e Y'),
            'ciudad' => config('lat90.company.city', 'Santiago de Chile'),
            'prestador_nombre' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
            'prestador_rut' => config('lat90.company.rut', '76.203.719-K'),
            'representante_nombre' => config('lat90.company.representative.name', 'Carolina Emhart García'),
            'representante_rut' => config('lat90.company.representative.rut', '13.670.825-2'),
            'domicilio_ciudad' => config('lat90.company.address.city', 'Santiago'),
            'domicilio_comuna' => config('lat90.company.address.commune', 'Providencia'),
            'domicilio_calle' => config('lat90.company.address.street', 'Carlos Antúnez 1941'),
            'apoderado_nombre' => $apoderadoNombre,
            'apoderado_rut' => $apoderadoDocument,
            'alumno_nombre' => $participantFullName,
            'alumno_documento_label' => $alumnoDocumentoLabel,
            'alumno_rut' => $participantDocumentNumber,
            'cotizacion_fecha' => $program->created_at->format('d \d\e F \d\e Y'),
            'programa_anio' => $program->departure_date ? $program->departure_date->format('Y') : date('Y'),
            'programa_nombre' => $program->name,
            'prestador_nombre_firma' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
            'firmante_nombre' => config('lat90.pdf.signature.name', 'Carmen Gutiérrez M.'),
            'firmante_cargo' => config('lat90.pdf.signature.position', 'Jefa área de recaudación'),
        ];
    }

    /**
     * Preparar datos específicos para el comprobante de pago
     */
    private function preparePaymentReceiptData(OrderDetail $orderDetail, Payment $payment, $program, $participant): array
    {
        // Obtener el tipo de documento del participante
        $documentType = $participant->documentType;
        $documentTypeName = $documentType ? $documentType->name : 'N/A';

        // Formatear número de documento del participante
        $documentNumber = $this->formatDocumentNumber($participant->document_number, $documentTypeName);

        // Obtener etiqueta del tipo de documento
        $documentTypeLabel = strtolower($documentTypeName) === 'rut' ? 'cédula nacional de identidad Nro.' : 'PASAPORTE';

        // Construir nombre completo del participante
        $participantFullName = $participant->full_name;

        // Calcular el monto total que debe pagar el participante
        $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        $totalDue = $priceData['final_price'];

        // Calcular saldo abonado
        $totalPaid = \App\Models\Payment::whereHas('orderDetail', function($query) use ($participant, $program) {
            $query->whereHas('order', function($q) use ($participant, $program) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $program->id);
            });
        })->where('status', 'completed')->sum('amount');

        return [
            'folio' => $program->code ?? 'N/A',
            'fecha' => $payment->created_at->format('d \d\e F, Y'),
            'monto' => number_format($payment->amount, 0, ',', '.'),
            'apoderado_nombre' => $orderDetail->name ?? 'N/A',
            'alumno_nombre' => $participantFullName,
            'alumno_rut' => $documentNumber,
            'document_type' => $documentTypeLabel,
            'valor_programa' => number_format($totalDue, 0, ',', '.'),
            'destino' => $program->destination ?? $program->name ?? 'N/A',
            'fecha_programa' => $program->departure_date ? $program->departure_date->format('F, Y') : 'Por definir',
            'monto_abono' => number_format($payment->amount, 0, ',', '.'),
            'fecha_abono' => $payment->created_at->format('d-m-Y'),
            'saldo_abonado' => number_format($totalPaid, 0, ',', '.'),
            'prestador_nombre_firma' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
            'firmante_nombre' => config('lat90.pdf.signature.name', 'Carmen Gutiérrez M.'),
            'firmante_cargo' => config('lat90.pdf.signature.position', 'Jefa área de recaudación'),
        ];
    }

    /**
     * Formatear número de documento según el tipo
     */
    private function formatDocumentNumber(string $documentNumber, string $documentType): string
    {
        if (strtolower($documentType) === 'rut') {
            $documentNumber = preg_replace('/[^0-9kK]/', '', $documentNumber);

            if (strlen($documentNumber) >= 8) {
                $dv = substr($documentNumber, -1);
                $numero = substr($documentNumber, 0, -1);
                $numero = number_format($numero, 0, '', '.');
                return $numero . '-' . strtoupper($dv);
            }
        }

        return strtoupper($documentNumber);
    }

    /**
     * Capitalizar palabras en una cadena
     */
    private function capitalizeWords(string $string): string
    {
        return ucwords(strtolower($string));
    }

    /**
     * Obtener imagen en base64
     */
    private function getImageBase64(string $path): string
    {
        $fullPath = resource_path($path);
        if (file_exists($fullPath)) {
            return base64_encode(file_get_contents($fullPath));
        }
        return '';
    }

    /**
     * Generar nombre de archivo
     */
    private function generateFilename(string $type, OrderDetail $orderDetail, Payment $payment): string
    {
        $prefix = $type === DocumentTemplate::TYPE_CONTRACT ? 'contrato' : 'comprobante_pago';
        return $prefix . '_' . $orderDetail->order->order_number . '_' . time() . '.pdf';
    }

    /**
     * Construir HTML completo con estilos
     */
    private function buildFullHtml(string $content, string $type): string
    {
        $styles = $this->getStyles();
        $footerLogoBase64 = $this->getImageBase64(config('lat90.pdf.logo.footer'));

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>{$type}</title>
    <style>{$styles}</style>
</head>
<body>
    <header></header>
    <footer>
        <div class="footer-inner">
            <img src="data:image/png;base64,{$footerLogoBase64}" alt="Lat90" style="width:1.02cm; height:1.34cm;" />
        </div>
    </footer>
    <main>
        {$content}
    </main>
</body>
</html>
HTML;
    }

    /**
     * Obtener estilos CSS para los PDFs
     */
    private function getStyles(): string
    {
        return <<<'CSS'
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

h1 {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    text-align: center;
    text-transform: uppercase;
    margin: 12px 0 4px 0;
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

ul, ol {
    font-family: 'Calibri Light', Calibri, sans-serif;
    font-size: 11px;
    margin: 6px 0 6px 18px;
}

.section {
    margin-bottom: 8px;
}

.folio {
    text-align: right;
    font-weight: bold;
    margin-bottom: 14px;
    font-size: 11px;
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

.center {
    text-align: center;
}

.mt-2 {
    margin-top: 6px;
}

.mt-4 {
    margin-top: 12px;
}

.mb-2 {
    margin-bottom: 6px;
}

.mb-4 {
    margin-bottom: 12px;
}

.bold {
    font-weight: bold;
}
CSS;
    }

    /**
     * Limpiar archivo temporal
     */
    public function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
