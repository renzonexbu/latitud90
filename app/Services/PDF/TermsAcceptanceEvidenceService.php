<?php

namespace App\Services\PDF;

use App\Models\DocumentTemplate;
use App\Models\OrderDetail;
use App\Traits\SystemLogging;
use Barryvdh\DomPDF\Facade\Pdf;

class TermsAcceptanceEvidenceService
{
    use SystemLogging;

    /**
     * Generar PDF de evidencia de aceptación de términos y condiciones
     */
    public function generatePdf(OrderDetail $orderDetail): string
    {
        try {
            $html = $this->buildFullHtml($orderDetail);

            $pdf = Pdf::loadHTML($html);

            // Generar nombre único para el archivo
            $filename = 'evidencia_tyc_' . $orderDetail->id . '_' . time() . '.pdf';

            // Guardar temporalmente el PDF
            $tempPath = storage_path('app/temp/' . $filename);

            // Asegurar que el directorio existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            $pdf->save($tempPath);

            $this->logInfo('TermsAcceptanceEvidenceService: PDF generado exitosamente', [
                'order_detail_id' => $orderDetail->id,
                'temp_path' => $tempPath,
            ]);

            return $tempPath;
        } catch (\Exception $e) {
            $this->logError('TermsAcceptanceEvidenceService: Error generando PDF', [
                'order_detail_id' => $orderDetail->id,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Generar PDF y retornar como stream para visualizar en navegador
     */
    public function download(OrderDetail $orderDetail): \Illuminate\Http\Response
    {
        $html = $this->buildFullHtml($orderDetail);

        $pdf = Pdf::loadHTML($html);

        $filename = 'Evidencia_TyC_' . $orderDetail->name . '_' . date('Y-m-d') . '.pdf';
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

        return $pdf->stream($filename);
    }

    /**
     * Construir HTML completo para el PDF usando la plantilla de la base de datos
     */
    private function buildFullHtml(OrderDetail $orderDetail): string
    {
        $styles = $this->getStyles();
        $footerLogoBase64 = $this->getImageBase64(config('lat90.pdf.logo.footer'));

        // Obtener la plantilla activa
        $template = DocumentTemplate::getActiveByType(DocumentTemplate::TYPE_TERMS_ACCEPTANCE_EVIDENCE);

        if (!$template) {
            throw new \Exception('No se encontró una plantilla activa para Evidencia de Aceptación de T&C');
        }

        // Preparar los datos para la plantilla
        $data = $this->prepareData($orderDetail);

        // Renderizar el contenido con los datos
        $content = $template->render($data);

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Evidencia de Aceptación de Términos y Condiciones</title>
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
     * Preparar los datos para reemplazar en la plantilla
     */
    private function prepareData(OrderDetail $orderDetail): array
    {
        // Cargar relaciones necesarias
        $orderDetail->load(['order.programCourse', 'order.participant', 'termsCondition']);

        // Datos del usuario
        $usuarioNombre = $orderDetail->name ?? 'N/A';
        $usuarioEmail = $orderDetail->email ?? 'N/A';

        // Tipo y número de documento
        $documentType = $orderDetail->documentType;
        $documentTypeName = $documentType ? $documentType->name : 'Documento';
        $documentNumber = $this->formatDocumentNumber($orderDetail->document_number, $documentTypeName);

        // Programa
        $programaCodigo = $orderDetail->order?->programCourse?->code ?? '';
        $programaNombre = $orderDetail->order?->programCourse?->name ?? 'N/A';
        $programa = $programaCodigo ? "{$programaCodigo} - {$programaNombre}" : $programaNombre;

        // Fecha de aceptación
        $fechaAceptacion = $orderDetail->terms_accepted_at
            ? $orderDetail->terms_accepted_at->format('Y-m-d H:i:s')
            : 'N/A';

        // IP y versión de T&C
        $ipAddress = $orderDetail->ip_address ?? 'N/A';
        $tcVersion = $orderDetail->termsCondition?->version ?? 'N/A';

        return [
            // Imágenes
            'logo_base64' => $this->getImageBase64(config('lat90.pdf.logo.header')),
            'divider_base64' => $this->getImageBase64(config('lat90.pdf.logo.divider')),

            // Datos de la empresa
            'empresa_direccion' => config('lat90.company.address.full', 'Carlos Antúnez 1941, Providencia'),
            'empresa_region' => config('lat90.company.address.region', 'Región Metropolitana'),
            'empresa_telefono' => config('lat90.company.phone', '+56988071858'),
            'empresa_sitio' => config('lat90.company.website', 'www.latitud90.com'),

            // Datos del usuario
            'usuario_nombre' => $usuarioNombre,
            'usuario_email' => $usuarioEmail,
            'documento_tipo' => $documentTypeName,
            'documento_numero' => $documentNumber,
            'programa' => $programa,

            // Datos del evento
            'evento_id' => $orderDetail->id,
            'fecha_aceptacion' => $fechaAceptacion,
            'ip_address' => $ipAddress,
            'tc_version' => $tcVersion,
        ];
    }

    /**
     * Obtener estilos CSS
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

.section {
    margin-bottom: 8px;
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
CSS;
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
     * Formatear número de documento según el tipo
     */
    private function formatDocumentNumber(?string $documentNumber, string $documentType): string
    {
        if (!$documentNumber) {
            return 'N/A';
        }

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
}
