<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Services\Admin\Reports\PaymentConfirmationLogsService;
use App\Services\Admin\Payments\ResendPaymentConfirmationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentConfirmationLogsController extends Controller
{
    protected $paymentLogsService;
    protected $resendService;

    public function __construct(
        PaymentConfirmationLogsService $paymentLogsService,
        ResendPaymentConfirmationService $resendService
    ) {
        $this->paymentLogsService = $paymentLogsService;
        $this->resendService = $resendService;
    }

    /**
     * Mostrar lista de historial de confirmaciones
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'program_id' => $request->input('program_id'),
            'participant_search' => $request->input('participant_search'),
            'status' => $request->input('status', 'all'),
            'payment_type' => $request->input('payment_type', 'all'),
            'per_page' => $request->input('per_page', 25),
        ];

        $data = $this->paymentLogsService->getPaymentLogs($filters);
        $programs = $this->paymentLogsService->getProgramsForFilter();

        return Inertia::render('Admin/Payments/Confirmations', [
            'payments' => $data['data'],
            'pagination' => $data['pagination'],
            'filters' => $filters,
            'programs' => $programs,
        ]);
    }

    /**
     * Obtener detalles de logs para un pago específico
     */
    public function show($paymentId)
    {
        $data = $this->paymentLogsService->getPaymentLogDetails($paymentId);

        return response()->json($data);
    }

    /**
     * Reenviar email de confirmación
     */
    public function resend(Request $request, $paymentId)
    {
        $userId = auth()->id();

        $result = $this->resendService->resendConfirmation($paymentId, $userId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * Reenviar solo el contrato de reserva (sin adjuntar otros documentos).
     * Útil para pagos antiguos donde el contrato no se envió.
     */
    public function resendContract(Request $request, $paymentId)
    {
        $userId = auth()->id();

        $result = $this->resendService->resendContract($paymentId, $userId);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }
}
