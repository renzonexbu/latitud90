<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BsaleRequest;
use App\Models\Payment;
use App\Services\Bsale\BsaleQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use ZipArchive;

class BsaleMonitorController extends Controller
{
    protected BsaleQueueService $queueService;

    public function __construct(BsaleQueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Vista principal del monitor BSale
     */
    public function index()
    {
        return Inertia::render('Admin/Bsale/Monitor');
    }

    /**
     * Obtener lista de solicitudes BSale con filtros
     */
    public function list(Request $request)
    {
        $query = BsaleRequest::with([
            'payment',
            'orderDetail.order.participant',
            'orderDetail.order.programCourse',
            'processedBy:id,name',
        ]);

        // Filtro por estado
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtro por tipo de documento
        if ($request->document_type && $request->document_type !== 'all') {
            $query->where('document_type', $request->document_type);
        }

        // Filtro por fecha desde
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro por fecha hasta
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Búsqueda por payment_id o bsale_number
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_id', $search)
                    ->orWhere('bsale_number', 'like', "%{$search}%")
                    ->orWhere('bsale_document_id', 'like', "%{$search}%")
                    ->orWhereHas('orderDetail.order.participant', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('first_last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Ordenar
        $sortBy = $request->sort_by ?? 'created_at';
        $sortDir = $request->sort_dir ?? 'desc';
        $query->orderBy($sortBy, $sortDir);

        // Paginar
        $perPage = min(max((int) $request->input('per_page', 25), 10), 100);
        $requests = $query->paginate($perPage);

        // Transformar datos para el frontend
        $requests->getCollection()->transform(function ($request) {
            return [
                'id' => $request->id,
                'payment_id' => $request->payment_id,
                'status' => $request->status,
                'status_label' => $request->status_label,
                'status_class' => $request->status_class,
                'document_type' => $request->document_type,
                'bsale_document_id' => $request->bsale_document_id,
                'bsale_number' => $request->bsale_number,
                'error_message' => $request->error_message,
                'error_code' => $request->error_code,
                'attempts' => $request->attempts,
                'max_attempts' => $request->max_attempts,
                'can_retry' => $request->canRetry(),
                'source' => $request->source,
                'scheduled_at' => $request->scheduled_at?->format('Y-m-d H:i:s'),
                'processed_at' => $request->processed_at?->format('Y-m-d H:i:s'),
                'created_at' => $request->created_at->format('Y-m-d H:i:s'),
                'payment' => $request->payment ? [
                    'id' => $request->payment->id,
                    'amount' => $request->payment->amount,
                    'status' => $request->payment->status,
                    'bsale_number' => $request->payment->bsale_number,
                ] : null,
                'participant' => $request->orderDetail?->order?->participant ? [
                    'name' => $request->orderDetail->order->participant->full_name,
                    'email' => $request->orderDetail->order->participant->email,
                ] : null,
                'program' => $request->orderDetail?->order?->programCourse ? [
                    'name' => $request->orderDetail->order->programCourse->name,
                    'code' => $request->orderDetail->order->programCourse->code,
                ] : null,
                'processed_by' => $request->processedBy?->name,
            ];
        });

        // Estadísticas
        $stats = $this->queueService->getQueueStats();

        return response()->json([
            'requests' => $requests,
            'stats' => $stats,
        ]);
    }

    /**
     * Obtener detalle de una solicitud
     */
    public function show(BsaleRequest $bsaleRequest)
    {
        $bsaleRequest->load([
            'payment',
            'orderDetail.order.participant',
            'orderDetail.order.programCourse',
            'processedBy',
        ]);

        return response()->json([
            'request' => [
                'id' => $bsaleRequest->id,
                'payment_id' => $bsaleRequest->payment_id,
                'order_detail_id' => $bsaleRequest->order_detail_id,
                'status' => $bsaleRequest->status,
                'status_label' => $bsaleRequest->status_label,
                'document_type' => $bsaleRequest->document_type,
                'request_data' => $bsaleRequest->request_data,
                'response_data' => $bsaleRequest->response_data,
                'bsale_document_id' => $bsaleRequest->bsale_document_id,
                'bsale_number' => $bsaleRequest->bsale_number,
                'bsale_token' => $bsaleRequest->bsale_token,
                'error_message' => $bsaleRequest->error_message,
                'error_code' => $bsaleRequest->error_code,
                'attempts' => $bsaleRequest->attempts,
                'max_attempts' => $bsaleRequest->max_attempts,
                'can_retry' => $bsaleRequest->canRetry(),
                'source' => $bsaleRequest->source,
                'metadata' => $bsaleRequest->metadata,
                'scheduled_at' => $bsaleRequest->scheduled_at?->format('Y-m-d H:i:s'),
                'processing_started_at' => $bsaleRequest->processing_started_at?->format('Y-m-d H:i:s'),
                'processed_at' => $bsaleRequest->processed_at?->format('Y-m-d H:i:s'),
                'last_attempt_at' => $bsaleRequest->last_attempt_at?->format('Y-m-d H:i:s'),
                'created_at' => $bsaleRequest->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $bsaleRequest->updated_at->format('Y-m-d H:i:s'),
                'payment' => $bsaleRequest->payment,
                'participant' => $bsaleRequest->orderDetail?->order?->participant,
                'program' => $bsaleRequest->orderDetail?->order?->programCourse,
                'processed_by' => $bsaleRequest->processedBy,
            ],
        ]);
    }

    /**
     * Reintentar una solicitud
     */
    public function retry(BsaleRequest $bsaleRequest)
    {
        if (!$bsaleRequest->canRetry()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud no puede reintentarse',
            ], 422);
        }

        $success = $this->queueService->retryRequest($bsaleRequest, Auth::id());

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitud programada para reintento',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo programar el reintento',
        ], 500);
    }

    /**
     * Forzar reprocesamiento
     */
    public function forceReprocess(BsaleRequest $bsaleRequest)
    {
        $success = $this->queueService->forceReprocess($bsaleRequest, Auth::id());

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitud forzada para reprocesamiento',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo forzar el reprocesamiento',
        ], 500);
    }

    /**
     * Cancelar una solicitud
     */
    public function cancel(BsaleRequest $bsaleRequest)
    {
        if ($bsaleRequest->isCompleted()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cancelar una solicitud completada',
            ], 422);
        }

        $bsaleRequest->cancel(Auth::id());

        return response()->json([
            'success' => true,
            'message' => 'Solicitud cancelada',
        ]);
    }

    /**
     * Procesar solicitudes pendientes manualmente (desde UI)
     */
    public function processQueue(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 10), 1), 50);

        $results = $this->queueService->processPendingRequests($limit);

        return response()->json([
            'success' => true,
            'message' => "Procesamiento completado: {$results['success']} exitosos, {$results['failed']} fallidos",
            'results' => $results,
        ]);
    }

    /**
     * Obtener solo estadísticas
     */
    public function stats()
    {
        $stats = $this->queueService->getQueueStats();

        // Agregar más estadísticas detalladas
        $stats['by_status'] = BsaleRequest::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $stats['by_source'] = BsaleRequest::selectRaw('source, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        $stats['last_7_days'] = BsaleRequest::selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->groupBy('date', 'status')
            ->get()
            ->groupBy('date')
            ->map(function ($items) {
                return $items->pluck('count', 'status')->toArray();
            })
            ->toArray();

        return response()->json($stats);
    }

    /**
     * Listar historial completo de boletas BSale (desde payments)
     * Incluye todas las boletas generadas, no solo las de la cola
     */
    public function invoiceHistory(Request $request)
    {
        $query = Payment::with([
            'order.participant',
            'order.programCourse',
            'orderDetail',
        ])
            ->whereNotNull('bsale_document_id')
            ->orWhereNotNull('bsale_number');

        // Filtro por fecha desde
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro por fecha hasta
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Búsqueda por bsale_number, payment_id o participante
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('bsale_number', 'like', "%{$search}%")
                    ->orWhere('bsale_document_id', 'like', "%{$search}%")
                    ->orWhereHas('order.participant', function ($sq) use ($search) {
                        $sq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('first_last_name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    });
            });
        }

        // Ordenar por fecha más reciente
        $query->orderBy('created_at', 'desc');

        // Paginar (permitir hasta 300 para el historial de boletas)
        $perPage = min(max((int) $request->input('per_page', 25), 10), 300);
        $invoices = $query->paginate($perPage);

        // Transformar datos
        $invoices->getCollection()->transform(function ($payment) {
            return [
                'id' => $payment->id,
                'bsale_document_id' => $payment->bsale_document_id,
                'bsale_number' => $payment->bsale_number,
                'bsale_token' => $payment->bsale_token,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'document_type' => $payment->document_type,
                'transaction_date' => $payment->transaction_date?->format('Y-m-d'),
                'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                'has_pdf' => !empty($payment->bsale_token),
                'pdf_url' => $payment->bsale_token
                    ? "https://app2.bsale.cl/view/90370/{$payment->bsale_token}.pdf?sfd=99"
                    : null,
                'participant' => $payment->order?->participant ? [
                    'name' => $payment->order->participant->full_name,
                    'rut' => $payment->order->participant->document_number,
                ] : null,
                'program' => $payment->order?->programCourse ? [
                    'name' => $payment->order->programCourse->name,
                    'code' => $payment->order->programCourse->code,
                ] : null,
            ];
        });

        // Estadísticas de boletas
        $totalInvoices = Payment::whereNotNull('bsale_document_id')->count();
        $invoicesThisMonth = Payment::whereNotNull('bsale_document_id')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'invoices' => $invoices,
            'stats' => [
                'total' => $totalInvoices,
                'this_month' => $invoicesThisMonth,
            ],
        ]);
    }

    /**
     * Descargar PDF de una boleta individual
     */
    public function downloadInvoicePdf(Payment $payment)
    {
        if (empty($payment->bsale_token)) {
            return back()->with('error', 'Esta boleta no tiene token para descargar el PDF');
        }

        $pdfUrl = "https://app2.bsale.cl/view/90370/{$payment->bsale_token}.pdf?sfd=99";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/pdf',
                ])
                ->get($pdfUrl);

            if ($response->successful()) {
                $contentType = $response->header('Content-Type');

                // Verificar que realmente sea un PDF
                if (strpos($contentType, 'application/pdf') === false &&
                    !str_starts_with($response->body(), '%PDF')) {
                    Log::channel('bsale')->warning('BSale no devolvió un PDF válido', [
                        'payment_id' => $payment->id,
                        'content_type' => $contentType,
                        'body_preview' => substr($response->body(), 0, 100),
                    ]);
                    return back()->with('error', 'BSale no devolvió un PDF válido');
                }

                $filename = "boleta_{$payment->bsale_number}_payment_{$payment->id}.pdf";

                return response($response->body())
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Length', strlen($response->body()))
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            }

            Log::channel('bsale')->warning('Error HTTP descargando PDF de BSale', [
                'payment_id' => $payment->id,
                'status' => $response->status(),
            ]);
            return back()->with('error', 'No se pudo descargar el PDF desde BSale (HTTP ' . $response->status() . ')');

        } catch (\Exception $e) {
            Log::channel('bsale')->error('Error descargando PDF de BSale', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Descargar múltiples boletas en un archivo ZIP
     * Soporta: payment_ids (array) O download_all con filtros
     */
    public function downloadInvoicesZip(Request $request)
    {
        // Si es descarga de todos, obtener IDs según filtros
        if ($request->download_all) {
            $query = Payment::whereNotNull('bsale_token')
                ->where(function ($q) {
                    $q->whereNotNull('bsale_document_id')
                        ->orWhereNotNull('bsale_number');
                });

            // Aplicar filtros
            if ($request->date_from) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->date_to) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
            if ($request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('id', $search)
                        ->orWhere('bsale_number', 'like', "%{$search}%")
                        ->orWhere('bsale_document_id', 'like', "%{$search}%");
                });
            }

            // Limitar a 500 boletas máximo para evitar timeouts
            $payments = $query->limit(500)->get();

            if ($payments->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron boletas con los filtros aplicados',
                ], 404);
            }
        } else {
            // Descarga de IDs específicos
            $request->validate([
                'payment_ids' => 'required|array|min:1|max:100',
                'payment_ids.*' => 'integer|exists:payments,id',
            ]);

            $payments = Payment::whereIn('id', $request->payment_ids)
                ->whereNotNull('bsale_token')
                ->get();
        }

        if ($payments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron boletas con PDF disponible',
            ], 404);
        }

        // Crear archivo ZIP temporal
        $zipFileName = 'boletas_bsale_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        // Asegurar que existe el directorio
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el archivo ZIP',
            ], 500);
        }

        $downloadedCount = 0;
        $errors = [];

        foreach ($payments as $payment) {
            $pdfUrl = "https://app2.bsale.cl/view/90370/{$payment->bsale_token}.pdf?sfd=99";

            try {
                $response = Http::timeout(30)->get($pdfUrl);

                if ($response->successful()) {
                    $filename = "boleta_{$payment->bsale_number}_payment_{$payment->id}.pdf";
                    $zip->addFromString($filename, $response->body());
                    $downloadedCount++;
                } else {
                    $errors[] = "Payment #{$payment->id}: Error HTTP {$response->status()}";
                }
            } catch (\Exception $e) {
                $errors[] = "Payment #{$payment->id}: {$e->getMessage()}";
                Log::channel('bsale')->warning('Error descargando PDF para ZIP', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $zip->close();

        if ($downloadedCount === 0) {
            unlink($zipPath);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo descargar ningún PDF',
                'errors' => $errors,
            ], 500);
        }

        Log::channel('bsale')->info('ZIP de boletas creado', [
            'total_requested' => $payments->count(),
            'downloaded' => $downloadedCount,
            'errors_count' => count($errors),
            'download_all' => $request->download_all ?? false,
        ]);

        return response()->download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Obtener URL del PDF de una boleta (para abrir en nueva pestaña)
     */
    public function getInvoicePdfUrl(Payment $payment)
    {
        if (empty($payment->bsale_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Esta boleta no tiene token para acceder al PDF',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'url' => "https://app2.bsale.cl/view/90370/{$payment->bsale_token}.pdf?sfd=99",
            'bsale_number' => $payment->bsale_number,
        ]);
    }

    /**
     * Sincronizar token de BSale para un payment individual
     */
    public function syncInvoiceToken(Payment $payment)
    {
        if (empty($payment->bsale_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Este pago no tiene número de boleta',
            ], 422);
        }

        if (!empty($payment->bsale_token)) {
            return response()->json([
                'success' => true,
                'message' => 'Este pago ya tiene token',
                'already_synced' => true,
            ]);
        }

        $bsaleService = app(\App\Services\Client\Integration\BsaleService::class);
        $success = $bsaleService->syncPaymentToken($payment);

        if ($success) {
            $payment->refresh();
            return response()->json([
                'success' => true,
                'message' => 'Token sincronizado correctamente',
                'bsale_token' => $payment->bsale_token,
                'pdf_url' => "https://app2.bsale.cl/view/90370/{$payment->bsale_token}.pdf?sfd=99",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró el documento en BSale con el número ' . $payment->bsale_number,
        ], 404);
    }

    /**
     * Sincronizar tokens de BSale en lote para pagos que tienen número pero no token
     */
    public function syncMissingTokens(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 50), 1), 200);

        // Buscar pagos con número de boleta pero sin token
        $payments = Payment::whereNotNull('bsale_number')
            ->whereNull('bsale_token')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        if ($payments->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No hay pagos pendientes de sincronizar',
                'synced' => 0,
                'failed' => 0,
                'total' => 0,
            ]);
        }

        $bsaleService = app(\App\Services\Client\Integration\BsaleService::class);
        $synced = 0;
        $failed = 0;
        $errors = [];

        foreach ($payments as $payment) {
            try {
                $success = $bsaleService->syncPaymentToken($payment);
                if ($success) {
                    $synced++;
                } else {
                    $failed++;
                    $errors[] = "Payment #{$payment->id} (Boleta {$payment->bsale_number}): No encontrado en BSale";
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Payment #{$payment->id}: " . $e->getMessage();
            }

            // Pequeña pausa para no sobrecargar la API de BSale
            usleep(200000); // 200ms
        }

        Log::channel('bsale')->info('BsaleMonitorController::syncMissingTokens - Sincronización completada', [
            'total' => $payments->count(),
            'synced' => $synced,
            'failed' => $failed,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sincronización completada: {$synced} actualizados, {$failed} fallidos",
            'synced' => $synced,
            'failed' => $failed,
            'total' => $payments->count(),
            'errors' => array_slice($errors, 0, 10), // Limitar errores mostrados
        ]);
    }

    /**
     * Obtener conteo de pagos pendientes de sincronizar token
     */
    public function getPendingSyncCount()
    {
        $count = Payment::whereNotNull('bsale_number')
            ->whereNull('bsale_token')
            ->count();

        return response()->json([
            'count' => $count,
        ]);
    }
}
