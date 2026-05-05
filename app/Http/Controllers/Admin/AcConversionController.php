<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Bsale\BsaleQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AcConversionController extends Controller
{
    /**
     * Listar pagos AC elegibles para conversión a boleta B2
     */
    public function index()
    {
        // Muestra TODOS los pagos AC pendientes (incluidos programas de año siguiente)
        // para que el admin pueda revisar/convertir antes de la fecha del programa
        // (ej: emitir boleta ante un reembolso por baja)
        $payments = Payment::where('document_type', 'AC')
            ->whereNull('bsale_number')
            ->where('status', 'completed')
            ->whereRaw("(gateway_response IS NULL OR JSON_EXTRACT(gateway_response, '$.ac_converted') IS NULL)")
            ->with([
                'order.participant.documentType',
                'order.programCourse',
                'orderDetail',
            ])
            ->orderBy('transaction_date', 'desc')
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
     * Ejecutar la conversión de pagos AC seleccionados a boleta B2.
     *
     * Por cada pago AC:
     *   1. Se registra un Reverso Administrativo (RA) interno — cancela el AC contablemente.
     *   2. Se crea un nuevo pago B2 con los mismos datos — equivale a un pago offline nuevo.
     *   3. Se genera la boleta BSale para el pago B2.
     *
     * El pago AC original queda intacto como registro histórico.
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
            $acPayment = Payment::where('id', $paymentId)
                ->where('document_type', 'AC')
                ->whereNull('bsale_number')
                ->where('status', 'completed')
                ->with(['order.programCourse', 'orderDetail', 'paymentGateway', 'paymentOption'])
                ->first();

            if (!$acPayment) {
                $results[] = [
                    'payment_id' => $paymentId,
                    'success' => false,
                    'message' => 'Pago no encontrado o ya procesado',
                ];
                continue;
            }

            try {
                DB::beginTransaction();

                // 1. Registrar Reverso Administrativo (RA) — cancela el AC internamente
                $raPayment = Payment::create([
                    'order_id'           => $acPayment->order_id,
                    'order_detail_id'    => $acPayment->order_detail_id,
                    'payment_gateway_id' => $acPayment->payment_gateway_id,
                    'payment_option_id'  => $acPayment->payment_option_id,
                    'buy_order'          => $acPayment->buy_order,
                    'amount'             => -abs($acPayment->amount),
                    'status'             => 'completed',
                    'transaction_date'   => now(),
                    'document_type'      => 'RA',
                    'currency'           => $acPayment->currency ?? 'CLP',
                    // Marcar como enviado para que el scheduler de emails lo omita:
                    // los reversos administrativos no generan comunicación al cliente.
                    'email_sent'         => true,
                    'email_sent_at'      => now(),
                    'gateway_response'   => [
                        'created_manually'    => true,
                        'payment_type'        => 'ac_conversion_reversal',
                        'original_payment_id' => $acPayment->id,
                        'notes'               => 'Reverso Administrativo — Conversión AC→Boleta',
                    ],
                ]);

                // 2. Crear nuevo pago B2 — mismo monto y datos del pagador original
                $b2Payment = Payment::create([
                    'order_id'           => $acPayment->order_id,
                    'order_detail_id'    => $acPayment->order_detail_id,
                    'payment_gateway_id' => $acPayment->payment_gateway_id,
                    'payment_option_id'  => $acPayment->payment_option_id,
                    'buy_order'          => $acPayment->buy_order,
                    'amount'             => abs($acPayment->amount),
                    'status'             => 'completed',
                    'transaction_date'   => now(),
                    'document_type'      => 'B2',
                    'currency'           => $acPayment->currency ?? 'CLP',
                    // Marcar como enviado para que el scheduler de emails lo omita:
                    // el email de la boleta lo despacha SendBsaleEmailJob con 1h de delay.
                    'email_sent'         => true,
                    'email_sent_at'      => now(),
                    'gateway_response'   => [
                        'created_manually'    => true,
                        'payment_type'        => 'ac_conversion_boleta',
                        'original_payment_id' => $acPayment->id,
                        'ra_payment_id'       => $raPayment->id,
                        'notes'               => 'Boleta generada por conversión AC→B2',
                    ],
                ]);

                DB::commit();

                // 3. Generar boleta BSale para el nuevo pago B2 (fuera de la transacción)
                $bsaleRequest = $bsaleQueueService->queueBoleta($b2Payment, 'ac_conversion');

                if (!$bsaleRequest) {
                    Log::warning('AcConversionController: No se pudo encolar en BSale', [
                        'original_payment_id' => $paymentId,
                        'b2_payment_id'       => $b2Payment->id,
                        'ra_payment_id'       => $raPayment->id,
                    ]);

                    $results[] = [
                        'payment_id' => $paymentId,
                        'success'    => false,
                        'message'    => 'RA y pago B2 registrados, pero no se pudo encolar en BSale.',
                    ];
                    continue;
                }

                $success = $bsaleQueueService->processRequest($bsaleRequest);
                $b2Payment->refresh();

                if ($success && $b2Payment->bsale_number) {
                    // Marcar el AC original como convertido para excluirlo del listado
                    $acPayment->gateway_response = array_merge($acPayment->gateway_response ?? [], [
                        'ac_converted'  => true,
                        'converted_at'  => now()->toISOString(),
                        'ra_payment_id' => $raPayment->id,
                        'b2_payment_id' => $b2Payment->id,
                    ]);
                    $acPayment->save();

                    Log::info('AcConversionController: Conversión completada', [
                        'original_payment_id' => $paymentId,
                        'ra_payment_id'       => $raPayment->id,
                        'b2_payment_id'       => $b2Payment->id,
                        'bsale_number'        => $b2Payment->bsale_number,
                    ]);

                    $results[] = [
                        'payment_id'   => $paymentId,
                        'success'      => true,
                        'bsale_number' => $b2Payment->bsale_number,
                        'message'      => "RA registrado + Boleta #{$b2Payment->bsale_number} generada",
                    ];
                } else {
                    $results[] = [
                        'payment_id' => $paymentId,
                        'success'    => false,
                        'message'    => 'RA y pago B2 registrados, pero BSale falló: ' . ($b2Payment->bsale_error ?? 'Error desconocido'),
                    ];
                }

            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('AcConversionController: Error en conversión', [
                    'payment_id' => $paymentId,
                    'error'      => $e->getMessage(),
                ]);

                $results[] = [
                    'payment_id' => $paymentId,
                    'success'    => false,
                    'message'    => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();
        $errorCount   = collect($results)->where('success', false)->count();

        return redirect()->route('admin.payments.ac-conversion.index')->with([
            'conversion_results' => $results,
            'success' => $successCount > 0
                ? "Se convirtieron {$successCount} pago(s) exitosamente." . ($errorCount > 0 ? " {$errorCount} con error." : '')
                : null,
            'error' => $successCount === 0
                ? 'No se pudo convertir ningún pago. Revisa los errores.'
                : null,
        ]);
    }
}
