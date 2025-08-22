<?php

namespace App\Http\Controllers;

use App\Services\EcommerceAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(EcommerceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Obtener análisis completo del funnel de conversión
     */
    public function getFunnelAnalysis(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $analysis = $this->analyticsService->getFunnelAnalysis($dateFrom, $dateTo);

        return response()->json($analysis);
    }

    /**
     * Registrar vista de lista de programas
     */
    public function recordProgramListView(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $this->analyticsService->recordProgramListView($request);

        return response()->json(['success' => true]);
    }

    /**
     * Registrar selección de programa
     */
    public function recordProgramSelection(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'enrollment_code' => 'nullable|string',
            'program_name' => 'nullable|string',
        ]);

        $this->analyticsService->recordProgramSelection(
            $request,
            $request->input('program_id'),
            $request->input('enrollment_code')
        );

        return response()->json(['success' => true]);
    }

    /**
     * Registrar selección de método de pago
     */
    public function recordPaymentSelection(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'payment_type' => 'required|string',
            'payment_method' => 'nullable|string',
            'installments' => 'nullable|integer',
            'amount' => 'nullable|numeric',
            'terms_accepted' => 'boolean',
        ]);

        $this->analyticsService->recordPaymentSelection(
            $request,
            $request->input('program_id'),
            [] // No necesitamos pasar paymentData ya que usamos $request->input()
        );

        return response()->json(['success' => true]);
    }

    /**
     * Registrar vista de detalle de programa
     */
    public function recordProgramDetailView(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
        ]);

        $this->analyticsService->recordProgramDetailView(
            $request,
            $request->input('program_id')
        );

        return response()->json(['success' => true]);
    }

    /**
     * Registrar vista de detalles de pago
     */
    public function recordPaymentDetailsView(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'participant_rut' => 'nullable|string',
        ]);

        $this->analyticsService->recordPaymentDetailsView(
            $request,
            $request->input('program_id'),
            $request->input('participant_rut')
        );

        return response()->json(['success' => true]);
    }

    /**
     * Registrar vista de confirmación
     */
    public function recordConfirmationView(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'participant_rut' => 'nullable|string',
        ]);

        $this->analyticsService->recordConfirmationView(
            $request,
            $request->input('program_id'),
            $request->input('participant_rut')
        );

        return response()->json(['success' => true]);
    }

    /**
     * Registrar pago iniciado
     */
    public function recordPaymentInitiated(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'participant_rut' => 'nullable|string',
            'order_id' => 'nullable|integer',
            'order_detail_id' => 'nullable|integer',
            'order_number' => 'nullable|string',
            'payment_data' => 'required|array',
        ]);

        $this->analyticsService->recordPaymentInitiated(
            $request,
            $request->input('program_id'),
            $request->input('participant_rut'),
            $request->input('payment_data'),
            [
                'order_id' => $request->input('order_id'),
                'order_detail_id' => $request->input('order_detail_id'),
                'order_number' => $request->input('order_number'),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Registrar pago completado
     */
    public function recordPaymentCompleted(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'participant_rut' => 'nullable|string',
            'payment_data' => 'required|array',
            'order_data' => 'nullable|array',
        ]);

        $this->analyticsService->recordPaymentCompleted(
            $request,
            $request->input('program_id'),
            $request->input('participant_rut'),
            $request->input('payment_data'),
            $request->input('order_data')
        );

        return response()->json(['success' => true]);
    }

    /**
     * Registrar pago fallido
     */
    public function recordPaymentFailed(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'participant_rut' => 'nullable|string',
            'error_message' => 'nullable|string',
        ]);

        $this->analyticsService->recordPaymentFailed(
            $request,
            $request->input('program_id'),
            $request->input('participant_rut'),
            $request->input('error_message')
        );

        return response()->json(['success' => true]);
    }
}
