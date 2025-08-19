<?php

namespace App\Services\PDF;

use App\Models\OrderDetail;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ContractService
{
    /**
     * Generar PDF del contrato
     */
    public function generateContract(OrderDetail $orderDetail, Payment $payment): string
    {
        try {
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

            Log::info('ContractService: PDF del contrato generado exitosamente', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'filename' => $filename,
            ]);

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('ContractService: Error generando PDF del contrato', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Preparar datos para el PDF del contrato
     */
    private function prepareContractData(OrderDetail $orderDetail, Payment $payment): array
    {
        $program = $orderDetail->order->program;
        $participant = $orderDetail->order->participant;

        // Formatear RUT del participante
        $participantDocumentNumber = $this->formatDocumentNumber($participant->document_number, $participant->document_type);

        // Generar folio del contrato: código del programa - RUT del participante (sin puntos ni guiones ni dígito verificador)
        $programCode = $program->code ?? 'PROG';
        $cleanRut = '';
        
        if (strtolower($participant->document_type) === 'rut') {
            $cleanRut = preg_replace('/[.-]/', '', $participant->document_number);
            $cleanRut = substr($cleanRut, 0, -1);
        } else {
            $cleanRut = preg_replace('/[.-]/', '', $participant->document_number);
        }
        
        $folio = $programCode . '-' . $cleanRut;

        return [
            // Datos del contrato
            'folio' => $folio,
            'fecha' => $payment->created_at->format('d \d\e F \d\e Y'),
            'ciudad' => config('lat90.company.city', 'Santiago de Chile'),

            // Datos del prestador de servicios
            'prestador_nombre' => config('lat90.company.legal_name', 'Experiencias Educativas y Capacitaciones SpA'),
            'prestador_rut' => config('lat90.company.rut', '76.203.719-K'),
            'representante_nombre' => config('lat90.company.representative.name', 'Carolina Emhart García'),
            'representante_rut' => config('lat90.company.representative.rut', '13.670.825-2'),
            'domicilio_ciudad' => config('lat90.company.address.city', 'Santiago'),
            'domicilio_comuna' => config('lat90.company.address.commune', 'Providencia'),
            'domicilio_calle' => config('lat90.company.address.street', 'Carlos Antúnez 1941'),

            // Datos del apoderado (datos del comprador desde order detail)
            'apoderado_nombre' => $orderDetail->name,
            'apoderado_rut' => $this->formatDocumentNumber($orderDetail->document_number, $orderDetail->document_type),

            // Datos del alumno/participante (desde la base de datos)
            'alumno_nombre' => $participant->full_name,
            'alumno_rut' => $participantDocumentNumber,

            // Datos del programa
            'cotizacion_fecha' => $program->created_at->format('d \d\e F \d\e Y'),
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
     * Limpiar archivos temporales
     */
    public function cleanupTempFile(string $filePath): void
    {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
