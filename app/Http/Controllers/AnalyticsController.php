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
     * Registrar vista de lista de programas
     */
    public function recordProgramListView(Request $request)
    {
        Log::info('AnalyticsController: recordProgramListView', [
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'session_id' => 'required|string',
        ]);

        $this->analyticsService->recordProgramListView($request);

        Log::info('AnalyticsController: recordProgramListView completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar selección de programa
     */
    public function recordProgramSelection(Request $request)
    {
        Log::info('AnalyticsController: recordProgramSelection', [
            'request_data' => $request->all(),
        ]);

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

        Log::info('AnalyticsController: recordProgramSelection completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar selección de método de pago
     */
    public function recordPaymentSelection(Request $request)
    {
        Log::info('AnalyticsController: recordPaymentSelection', [
            'request_data' => $request->all(),
        ]);

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

        Log::info('AnalyticsController: recordPaymentSelection completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar vista de detalle de programa
     */
    public function recordProgramDetailView(Request $request)
    {
        Log::info('AnalyticsController: recordProgramDetailView', [
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
        ]);

        $this->analyticsService->recordProgramDetailView(
            $request,
            $request->input('program_id')
        );

        Log::info('AnalyticsController: recordProgramDetailView completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar vista de detalles de pago
     */
    public function recordPaymentDetailsView(Request $request)
    {
        Log::info('AnalyticsController: recordPaymentDetailsView', [
            'request_data' => $request->all(),
        ]);

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

        Log::info('AnalyticsController: recordPaymentDetailsView completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar vista de confirmación
     */
    public function recordConfirmationView(Request $request)
    {
        Log::info('AnalyticsController: recordConfirmationView', [
            'request_data' => $request->all(),
        ]);

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

        Log::info('AnalyticsController: recordConfirmationView completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar datos del formulario del comprador
     */
    public function recordBuyerFormData(Request $request)
    {
        Log::info('AnalyticsController: recordBuyerFormData', [
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'form_data' => 'required|array',
        ]);

        $this->analyticsService->recordBuyerFormData(
            $request,
            $request->input('program_id'),
            $request->input('form_data')
        );

        Log::info('AnalyticsController: recordBuyerFormData completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar pago iniciado
     */
    public function recordPaymentInitiated(Request $request)
    {
        Log::info('AnalyticsController: recordPaymentInitiated', [
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'participant_rut' => 'nullable|string',
            'payment_data' => 'required|array',
        ]);

        $this->analyticsService->recordPaymentInitiated(
            $request,
            $request->input('program_id'),
            $request->input('participant_rut'),
            $request->input('payment_data')
        );

        Log::info('AnalyticsController: recordPaymentInitiated completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar pago completado
     */
    public function recordPaymentCompleted(Request $request)
    {
        Log::info('AnalyticsController: recordPaymentCompleted', [
            'request_data' => $request->all(),
        ]);

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

        Log::info('AnalyticsController: recordPaymentCompleted completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar pago fallido
     */
    public function recordPaymentFailed(Request $request)
    {
        Log::info('AnalyticsController: recordPaymentFailed', [
            'request_data' => $request->all(),
        ]);

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

        Log::info('AnalyticsController: recordPaymentFailed completado');

        return response()->json(['success' => true]);
    }

    /**
     * Registrar cambios en confirmación
     */
    public function recordConfirmationChanges(Request $request)
    {
        Log::info('AnalyticsController: recordConfirmationChanges', [
            'request_data' => $request->all(),
        ]);

        $request->validate([
            'session_id' => 'required|string',
            'program_id' => 'required|integer',
            'changes' => 'required|array',
        ]);

        $this->analyticsService->recordConfirmationChanges(
            $request,
            $request->input('program_id'),
            $request->input('changes')
        );

        Log::info('AnalyticsController: recordConfirmationChanges completado');

        return response()->json(['success' => true]);
    }
}
