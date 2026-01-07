<?php

namespace App\Services;

use App\Models\GeneratedDocument;
use App\Models\OrderDetail;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    /**
     * Almacena un comprobante de pago y lo registra en la base de datos
     */
    public function storePaymentReceipt(
        string $tempFilePath,
        Payment $payment,
        OrderDetail $orderDetail,
        string $generatedFrom = GeneratedDocument::SOURCE_PAYMENT_CONFIRMATION
    ): ?GeneratedDocument {
        try {
            $year = now()->year;
            $fileName = "comprobante_pago_{$payment->id}.pdf";
            $storagePath = "payment_receipts/{$year}/{$fileName}";

            // Mover de temp a ubicación permanente
            if (Storage::exists($tempFilePath)) {
                Storage::move($tempFilePath, $storagePath);
            } else {
                Log::warning("Archivo temporal no encontrado: {$tempFilePath}");
                return null;
            }

            // Registrar en base de datos
            $order = $orderDetail->order;

            return GeneratedDocument::createDocument(
                GeneratedDocument::TYPE_PAYMENT_RECEIPT,
                $storagePath,
                $fileName,
                [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                    'participant_id' => $order->participant_id,
                    'program_id' => $order->program_id,
                    'generated_from' => $generatedFrom,
                ],
                [
                    'order_number' => $order->order_number,
                    'payment_amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error storing payment receipt: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'order_detail_id' => $orderDetail->id,
                'temp_file' => $tempFilePath,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Almacena un contrato y lo registra en la base de datos
     */
    public function storeContract(
        string $tempFilePath,
        OrderDetail $orderDetail,
        string $generatedFrom = GeneratedDocument::SOURCE_PAYMENT_CONFIRMATION
    ): ?GeneratedDocument {
        try {
            $fileName = "contrato_{$orderDetail->participant_id}_{$orderDetail->program_id}.pdf";
            $storagePath = "contracts/{$fileName}";

            // Mover de temp a ubicación permanente
            if (Storage::exists($tempFilePath)) {
                // Si ya existe un contrato, eliminarlo
                if (Storage::exists($storagePath)) {
                    Storage::delete($storagePath);
                }
                Storage::move($tempFilePath, $storagePath);
            } else {
                Log::warning("Archivo temporal no encontrado: {$tempFilePath}");
                return null;
            }

            // Obtener orden para acceder a participant_id y program_id
            $order = $orderDetail->order;

            // Verificar si ya existe un registro de contrato para este participante/programa
            $existingDoc = GeneratedDocument::where('document_type', GeneratedDocument::TYPE_CONTRACT)
                ->where('participant_id', $order->participant_id)
                ->where('program_id', $order->program_id)
                ->first();

            if ($existingDoc) {
                // Actualizar el registro existente
                $existingDoc->update([
                    'file_path' => $storagePath,
                    'file_name' => $fileName,
                    'file_size' => Storage::size($storagePath),
                    'order_detail_id' => $orderDetail->id,
                    'generated_from' => $generatedFrom,
                    'updated_at' => now(),
                ]);
                return $existingDoc;
            }

            // Crear nuevo registro
            return GeneratedDocument::createDocument(
                GeneratedDocument::TYPE_CONTRACT,
                $storagePath,
                $fileName,
                [
                    'order_detail_id' => $orderDetail->id,
                    'participant_id' => $order->participant_id,
                    'program_id' => $order->program_id,
                    'generated_from' => $generatedFrom,
                ],
                [
                    'order_number' => $order->order_number,
                    'program_name' => $order->program->name ?? 'N/A',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error storing contract: ' . $e->getMessage(), [
                'order_detail_id' => $orderDetail->id,
                'participant_id' => $orderDetail->participant_id,
                'program_id' => $orderDetail->program_id,
                'temp_file' => $tempFilePath,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Almacena una boleta Bsale y la registra en la base de datos
     */
    public function storeBsaleInvoice(
        string $tempFilePath,
        Payment $payment,
        OrderDetail $orderDetail,
        string $bsaleNumber,
        string $generatedFrom = GeneratedDocument::SOURCE_PAYMENT_CONFIRMATION
    ): ?GeneratedDocument {
        try {
            $year = now()->year;
            $fileName = "bsale_{$bsaleNumber}_payment_{$payment->id}.pdf";
            $storagePath = "bsale_documents/{$year}/{$fileName}";

            // Mover de temp a ubicación permanente
            if (Storage::exists($tempFilePath)) {
                Storage::move($tempFilePath, $storagePath);
            } else {
                Log::warning("Archivo temporal no encontrado: {$tempFilePath}");
                return null;
            }

            // Registrar en base de datos
            $order = $orderDetail->order;

            return GeneratedDocument::createDocument(
                GeneratedDocument::TYPE_BSALE_INVOICE,
                $storagePath,
                $fileName,
                [
                    'payment_id' => $payment->id,
                    'order_detail_id' => $orderDetail->id,
                    'participant_id' => $order->participant_id,
                    'program_id' => $order->program_id,
                    'generated_from' => $generatedFrom,
                ],
                [
                    'bsale_number' => $bsaleNumber,
                    'order_number' => $order->order_number,
                    'payment_amount' => $payment->amount,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Error storing Bsale invoice: ' . $e->getMessage(), [
                'payment_id' => $payment->id,
                'order_detail_id' => $orderDetail->id,
                'bsale_number' => $bsaleNumber,
                'temp_file' => $tempFilePath,
                'exception' => $e,
            ]);
            return null;
        }
    }

    /**
     * Registra que un documento fue enviado por email
     */
    public function markDocumentAsEmailSent(
        GeneratedDocument $document,
        string $emailTo
    ): void {
        try {
            $document->markAsEmailSent($emailTo);

            Log::info('Document marked as email sent', [
                'document_id' => $document->id,
                'document_type' => $document->document_type,
                'email_to' => $emailTo,
                'send_count' => $document->email_send_count,
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking document as email sent: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'email_to' => $emailTo,
                'exception' => $e,
            ]);
        }
    }

    /**
     * Obtiene todos los documentos de un participante en un programa
     */
    public function getParticipantProgramDocuments(int $participantId, int $programId)
    {
        return GeneratedDocument::where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtiene todos los documentos de un pago
     */
    public function getPaymentDocuments(int $paymentId)
    {
        return GeneratedDocument::where('payment_id', $paymentId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Verifica si existe un documento
     */
    public function documentExists(GeneratedDocument $document): bool
    {
        return $document->exists();
    }

    /**
     * Limpia archivos temporales antiguos
     */
    public function cleanupOldTempFiles(int $hoursOld = 24): int
    {
        try {
            $tempFiles = Storage::files('temp');
            $deleted = 0;
            $cutoffTime = now()->subHours($hoursOld)->timestamp;

            foreach ($tempFiles as $file) {
                if (Storage::lastModified($file) < $cutoffTime) {
                    Storage::delete($file);
                    $deleted++;
                }
            }

            Log::info("Cleaned up {$deleted} temporary files older than {$hoursOld} hours");
            return $deleted;
        } catch (\Exception $e) {
            Log::error('Error cleaning up temp files: ' . $e->getMessage());
            return 0;
        }
    }
}
