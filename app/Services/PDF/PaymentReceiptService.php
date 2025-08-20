<?php

namespace App\Services\PDF;

use App\Models\OrderDetail;
use App\Models\Payment;
use App\Helpers\ParticipantPriceHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PaymentReceiptService
{
    /**
     * Generar PDF de comprobante de pago
     */
    public function generatePaymentReceipt(OrderDetail $orderDetail, Payment $payment): string
    {
        try {
            $data = $this->preparePdfData($orderDetail, $payment);
            
            $pdf = Pdf::loadView('PDF.payment', $data);
            
            // Generar nombre único para el archivo
            $filename = 'comprobante_pago_' . $orderDetail->order->order_number . '_' . time() . '.pdf';
            
            // Guardar temporalmente el PDF
            $tempPath = storage_path('app/temp/' . $filename);
            
            // Asegurar que el directorio existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }
            
            $pdf->save($tempPath);
            
            Log::info('PaymentReceiptService: PDF generado exitosamente', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'filename' => $filename,
            ]);
            
            return $tempPath;
        } catch (\Exception $e) {
            Log::error('PaymentReceiptService: Error generando PDF', [
                'order_detail_id' => $orderDetail->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Preparar datos para el PDF
     */
    private function preparePdfData(OrderDetail $orderDetail, Payment $payment): array
    {
        $program = $orderDetail->order->program;
        $participant = $orderDetail->order->participant;
        
        // Obtener el tipo de documento del participante desde la tabla document
        $documentType = $participant->documentType;
        $documentTypeName = $documentType ? $documentType->name : 'N/A';
        
        // Formatear número de documento del participante
        $documentNumber = $this->formatDocumentNumber($participant->document_number, $documentTypeName);
        
        // Obtener etiqueta del tipo de documento
        $documentTypeLabel = $this->getDocumentTypeLabel($documentTypeName);
        
        // Construir nombre completo del participante
        $participantFullName = $this->buildParticipantFullName($participant);
        
        // Calcular el monto total que debe pagar el participante usando el helper
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        $totalDue = $priceData['final_price'];
        
        // Calcular saldo abonado (suma de todos los pagos del participante para este programa)
        $totalPaid = Payment::whereHas('orderDetail', function($query) use ($participant, $program) {
            $query->whereHas('order', function($q) use ($participant, $program) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $program->id);
            });
        })->where('status', 'completed')->sum('amount');
        
        return [
            // Datos del comprobante
            'folio' => $program->code ?? 'N/A',
            'fecha' => $payment->created_at->format('d \d\e F, Y'),
            'monto' => number_format($payment->amount, 0, ',', '.'),
            
            // Datos del comprador/apoderado
            'apoderado_nombre' => $orderDetail->name ?? 'N/A',
            
            // Datos del alumno/participante
            'alumno_nombre' => $participantFullName,
            'alumno_rut' => $documentNumber,
            'document_type' => $documentTypeLabel,
            
            // Datos del programa
            'valor_programa' => number_format($totalDue, 0, ',', '.'),
            'destino' => $program->destination ?? $program->name ?? 'N/A',
            'fecha_programa' => $program->departure_date ? $program->departure_date->format('F, Y') : 'Por definir',
            
            // Datos del abono
            'monto_abono' => number_format($payment->amount, 0, ',', '.'),
            'fecha_abono' => $payment->created_at->format('d-m-Y'),
            'saldo_abonado' => number_format($totalPaid, 0, ',', '.'),
            
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
     * Obtener etiqueta del tipo de documento
     */
    private function getDocumentTypeLabel(string $documentType): string
    {
        return strtolower($documentType) === 'rut' ? 'cédula nacional de identidad Nro.' : 'PASAPORTE';
    }

    /**
     * Construir nombre completo del participante
     */
    private function buildParticipantFullName($participant): string
    {
        $parts = [];
        
        // Primero nombres, luego apellidos
        if ($participant->first_name) {
            $parts[] = $this->capitalizeWords($participant->first_name);
        }
        if ($participant->second_name) {
            $parts[] = $this->capitalizeWords($participant->second_name);
        }
        if ($participant->first_last_name) {
            $parts[] = $this->capitalizeWords($participant->first_last_name);
        }
        if ($participant->second_last_name) {
            $parts[] = $this->capitalizeWords($participant->second_last_name);
        }
        
        return !empty($parts) ? implode(' ', $parts) : 'N/A';
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
