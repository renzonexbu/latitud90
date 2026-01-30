<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BsaleRequest;
use App\Services\Bsale\BsaleQueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

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
}
