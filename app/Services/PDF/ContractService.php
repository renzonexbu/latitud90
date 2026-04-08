<?php

namespace App\Services\PDF;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Traits\SystemLogging;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractService
{
    use SystemLogging;
    /**
     * Generar PDF del contrato
     */
    public function generateContract(OrderDetail $orderDetail, Payment $payment): string
    {
        try {
            // Intentar usar plantillas de base de datos primero
            try {
                $templateService = app(DocumentTemplateService::class);
                $tempPath = $templateService->generatePdfFromTemplate(
                    \App\Models\DocumentTemplate::TYPE_CONTRACT,
                    $orderDetail,
                    $payment
                );

                $this->logInfo('ContractService: PDF generado usando plantilla de BD', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                ]);

                return $tempPath;
            } catch (\Exception $templateException) {
                // Si falla la plantilla de BD, usar el método legacy (Blade)
                $this->logWarning('ContractService: Fallback a plantilla Blade', [
                    'order_detail_id' => $orderDetail->id,
                    'payment_id' => $payment->id,
                    'error' => $templateException->getMessage(),
                ]);

                return $this->generateContractLegacy($orderDetail, $payment);
            }
        } catch (\Exception $e) {
            $this->logError('ContractService: Error generando PDF del contrato', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ], $e);

            throw $e;
        }
    }

    /**
     * Generar PDF del contrato usando método legacy (Blade)
     */
    private function generateContractLegacy(OrderDetail $orderDetail, Payment $payment): string
    {
        $data = $this->prepareContractData($orderDetail, $payment);

        $pdf = Pdf::loadView('PDF.contract', $data);

        // Generar nombre único para el archivo
        $filename = 'contrato_' . $orderDetail->order->order_number . '_' . time() . '.pdf';

        // Guardar temporalmente el PDF
        $tempPath = storage_path('app/temp/' . $filename);

        // Asegurar que el directorio existe
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $pdf->save($tempPath);

        $this->logInfo('ContractService: PDF del contrato generado exitosamente (legacy)', [
            'order_detail_id' => $orderDetail->id,
            'payment_id' => $payment->id,
            'filename' => $filename,
        ]);

        return $tempPath;
    }

    /**
     * Preparar datos para el PDF del contrato
     */
    private function prepareContractData(OrderDetail $orderDetail, Payment $payment): array
    {
        // En la arquitectura nueva, program_id en Order apunta a ProgramCourse
        $programCourse = $orderDetail->order->programCourse;
        $program = $programCourse; // alias para compatibilidad con el resto del método
        $participant = $orderDetail->order->participant;

        // Obtener el tipo de documento del participante desde la tabla document
        $documentType = $participant->documentType;
        $documentTypeName = $documentType ? $documentType->name : 'N/A';

        // Formatear número de documento del participante
        $participantDocumentNumber = $this->formatDocumentNumber($participant->document_number, $documentTypeName);

        // Obtener el contacto de emergencia como apoderado
        $emergencyContact = $participant->emergencyContacts()->first();
        $apoderadoNombre = $emergencyContact ? $this->capitalizeWords($emergencyContact->name) : 'N/A';
        $apoderadoDocument = $emergencyContact ? $this->formatDocumentNumber($emergencyContact->document_number, 'RUT') : 'N/A';

        // Generar folio del contrato: código del programa - documento del participante (sin puntos ni guiones ni dígito verificador)
        $programCode = $program->code ?? 'PROG';
        $cleanDocument = '';
        
        if (strtolower($documentTypeName) === 'rut') {
            $cleanDocument = preg_replace('/[.-]/', '', $participant->document_number);
            $cleanDocument = substr($cleanDocument, 0, -1); // Sin dígito verificador
        } else {
            $cleanDocument = preg_replace('/[.-]/', '', $participant->document_number);
        }
        
        $folio = $programCode . '-' . $cleanDocument;

        // Construir nombre completo del participante
        $participantFullName = $this->buildParticipantFullName($participant);

        return [
            // Datos del contrato
            'folio' => $folio,
            'fecha' => $payment->created_at->locale('es')->translatedFormat('d \d\e F \d\e Y'),
            'ciudad' => config('lat90.company.city', 'Santiago de Chile'),

            // Datos del prestador de servicios
            'prestador_nombre' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
            'prestador_rut' => config('lat90.company.rut', '76.203.719-K'),
            'representante_nombre' => config('lat90.company.representative.name', 'Carolina Emhart García'),
            'representante_rut' => config('lat90.company.representative.rut', '13.670.825-2'),
            'domicilio_ciudad' => config('lat90.company.address.city', 'Santiago'),
            'domicilio_comuna' => config('lat90.company.address.commune', 'Providencia'),
            'domicilio_calle' => config('lat90.company.address.street', 'Carlos Antúnez 1941'),

            // Datos del apoderado (contacto de emergencia)
            'apoderado_nombre' => $apoderadoNombre,
            'apoderado_rut' => $apoderadoDocument,
            'apoderado_tipo_documento' => $emergencyContact && $emergencyContact->documentType ? $emergencyContact->documentType->name : 'N/A',

            // Datos del alumno/participante
            'alumno_nombre' => $participantFullName,
            'alumno_rut' => $participantDocumentNumber,
            'alumno_tipo_documento' => $documentTypeName,

            // Datos del programa
            'cotizacion_fecha' => $program->created_at->locale('es')->translatedFormat('d \d\e F \d\e Y'),
            'programa_anio' => $program->departure_date ? $program->departure_date->format('Y') : date('Y'),
            'programa_nombre' => $program->name,

            // Datos de la empresa
            'empresa_direccion' => config('lat90.company.address.full', 'Carlos Antúnez 1941, Providencia'),
            'empresa_region' => config('lat90.company.address.region', 'Región Metropolitana'),
            'empresa_telefono' => config('lat90.company.phone', '+56 9 7909 1738'),
            'empresa_sitio' => config('lat90.company.website', 'www.latitud90.com'),

            // Datos del firmante
            'prestador_nombre_firma' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
            'firmante_nombre' => config('lat90.pdf.signature.name', 'Carmen Gutiérrez M.'),
            'firmante_cargo' => config('lat90.pdf.signature.position', 'Jefa área de recaudación'),
        ];
    }

    /**
     * Construir nombre completo del participante
     */
    private function buildParticipantFullName($participant): string
    {
        if (!$participant) {
            return 'N/A';
        }
        
        // Usar directamente el accessor full_name del modelo
        return $participant->full_name;
    }

    /**
     * Formatear número de documento según el tipo
     */
    private function formatDocumentNumber(string $documentNumber, string $documentType): string
    {
        if (strtolower($documentType) === 'rut') {
            // Formatear RUT: 12345678 -> 12.345.678-9
            $documentNumber = preg_replace('/[^0-9kK]/', '', $documentNumber);

            if (strlen($documentNumber) >= 8) {
                $dv = substr($documentNumber, -1);
                $numero = substr($documentNumber, 0, -1);
                $numero = number_format($numero, 0, '', '.');
                return $numero . '-' . strtoupper($dv);
            }
        }

        // Para pasaporte, devolver en mayúsculas
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
     * Limpiar archivos temporales
     */
    public function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
