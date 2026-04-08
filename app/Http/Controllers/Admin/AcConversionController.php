<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Bsale\BsaleQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AcConversionController extends Controller
{
    /**
     * Listar pagos AC elegibles para conversión a boleta B2
     */
    public function index()
    {
        $payments = Payment::where('document_type', 'AC')
            ->whereNull('bsale_number')
            ->where('status', 'completed')
            ->whereHas('order.programCourse', function ($q) {
                $q->whereYear('departure_date', '<=', now()->year);
            })
            ->with([
                'order.participant.documentType',
                'order.programCourse',
                'orderDetail',
            ])
            ->orderBy('transaction_date', 'asc')
            ->get()
            ->map(function ($payment) {
                $participant = $payment->order->participant;
                $programCourse = $payment->order->programCourse;
                $docType = $participant->documentType->name ?? 'DOC';

                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'transaction_date' => $payment->transaction_date,
                    'document_type' => $payment->document_type,
                    'participant' => [
                        'id' => $participant->id,
                        'full_name' => trim(
                            ($participant->first_name ?? '') . ' ' .
                            ($participant->first_last_name ?? '')
                        ),
                        'document_type' => $docType,
                        'document_number' => $participant->document_number,
                    ],
                    'program' => [
                        'id' => $programCourse->id,
                        'code' => $programCourse->code,
                        'name' => $programCourse->name,
                        'departure_year' => $programCourse->departure_date
                            ? $programCourse->departure_date->format('Y')
                            : null,
                        'departure_date' => $programCourse->departure_date,
                    ],
                ];
            });

        return Inertia::render('Admin/Payments/AcConversion', [
            'payments' => $payments,
            'total_amount' => $payments->sum('amount'),
        ]);
    }

    /**
     * Ejecutar la conversión de pagos AC seleccionados a boleta B2
     */
    public function execute(Request $request)
    {
        $request->validate([
            'payment_ids' => 'required|array|min:1',
            'payment_ids.*' => 'integer|exists:payments,id',
        ]);

        $bsaleQueueService = app(BsaleQueueService::class);
        $results = [];

        foreach ($request->payment_ids as $paymentId) {
            $payment = Payment::where('id', $paymentId)
                ->where('document_type', 'AC')
                ->whereNull('bsale_number')
                ->where('status', 'completed')
                ->first();

            if (!$payment) {
                $results[] = [
                    'payment_id' => $paymentId,
                    'success' => false,
                    'message' => 'Pago no encontrado o ya procesado',
                ];
                continue;
            }

            try {
                // Cambiar document_type a B2 para que BSale genere la boleta
                $payment->document_type = 'B2';
                $payment->save();

                // Encolar y procesar inmediatamente
                $bsaleRequest = $bsaleQueueService->queueBoleta($payment, 'ac_conversion');

                if (!$bsaleRequest) {
                    // Revertir si no se pudo encolar
                    $payment->document_type = 'AC';
                    $payment->save();

                    $results[] = [
                        'payment_id' => $paymentId,
                        'success' => false,
                        'message' => 'No se pudo encolar en BSale (ya existe o no aplica)',
                    ];
                    continue;
                }

                $success = $bsaleQueueService->processRequest($bsaleRequest);
                $payment->refresh();

                if ($success && $payment->bsale_number) {
                    Log::info('AcConversionController: Boleta generada exitosamente', [
                        'payment_id' => $paymentId,
                        'bsale_number' => $payment->bsale_number,
                    ]);

                    $results[] = [
                        'payment_id' => $paymentId,
                        'success' => true,
                        'bsale_number' => $payment->bsale_number,
                        'message' => "Boleta #{$payment->bsale_number} generada",
                    ];
                } else {
                    // Revertir document_type si falló
                    $payment->document_type = 'AC';
                    $payment->save();

                    $results[] = [
                        'payment_id' => $paymentId,
                        'success' => false,
                        'message' => 'Error al generar boleta en BSale: ' . ($payment->bsale_error ?? 'Error desconocido'),
                    ];
                }
            } catch (\Exception $e) {
                // Revertir document_type en caso de excepción
                $payment->document_type = 'AC';
                $payment->save();

                Log::error('AcConversionController: Excepción al convertir pago', [
                    'payment_id' => $paymentId,
                    'error' => $e->getMessage(),
                ]);

                $results[] = [
                    'payment_id' => $paymentId,
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();
        $errorCount = collect($results)->where('success', false)->count();

        return redirect()->route('admin.payments.ac-conversion.index')->with([
            'conversion_results' => $results,
            'success' => $successCount > 0
                ? "Se generaron {$successCount} boleta(s) exitosamente." . ($errorCount > 0 ? " {$errorCount} con error." : '')
                : null,
            'error' => $successCount === 0
                ? "No se pudo generar ninguna boleta. Revisa los errores."
                : null,
        ]);
    }
}
