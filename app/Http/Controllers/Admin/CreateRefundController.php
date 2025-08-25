<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Region;
use App\Services\Admin\Payments\CreateRefundService;
use App\Models\Payment;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CreateRefundController extends Controller
{
    public function __construct(
        private CreateRefundService $createRefundService
    ) {}

    /**
     * Mostrar el formulario de creación de reembolso
     */
    public function create()
    {
        // Obtener datos necesarios para el formulario
        $programs = Program::with(['course.participants'])->where('active', true)->get();
        $countries = Country::where('name', 'Chile')->get();
        $regions = Region::with('comunes')->get();
        $documentTypes = Document::all();
        
        return Inertia::render('Admin/Payments/Refunds', [
            'programs' => $programs,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes
        ]);
    }

    /**
     * Almacenar el reembolso
     */
    public function store(Request $request)
    {
        // Validar datos requeridos
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:programs,id',
            'participant_id' => 'required|exists:participants,id',
            'amount' => 'required|numeric|min:1',
            'payment_code' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'authorization_code' => 'nullable|string|max:255',
            'notes' => 'required|string',
            
            // Validar datos del comprador
            'buyer_full_name' => 'required|string|max:255',
            'buyer_document_type' => 'required|exists:document,id',
            'buyer_document_number' => 'required|string|max:255',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'required|string|max:20',
            'buyer_code_phone' => 'required|string|max:10',
            'buyer_country' => 'required|exists:countries,id',
            'buyer_region' => 'required|exists:regions,id',
            'buyer_city' => 'required|exists:comunes,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Preparar datos para el servicio
            $refundData = [
                'program_id' => $request->program_id,
                'participant_id' => $request->participant_id,
                'amount' => $request->amount,
                'transaction_date' => $request->transaction_date,
                'payment_code' => $request->payment_code,
                'authorization_code' => $request->authorization_code,
                'notes' => $request->notes,
                
                // Datos del comprador
                'buyer_full_name' => $request->buyer_full_name,
                'buyer_document_type' => $request->buyer_document_type,
                'buyer_document_number' => $request->buyer_document_number,
                'buyer_email' => $request->buyer_email,
                'buyer_phone' => $request->buyer_phone,
                'buyer_code_phone' => $request->buyer_code_phone,
                'buyer_country' => $request->buyer_country,
                'buyer_region' => $request->buyer_region,
                'buyer_city' => $request->buyer_city,
            ];

            // Ejecutar el servicio
            $result = $this->createRefundService->execute($refundData);

            if ($result['success']) {
                return redirect()->route('admin.payments.index')
                    ->with('success', 'Reembolso procesado exitosamente. Se han reestructurado las cuotas pendientes.');
            } else {
                return back()->withErrors(['error' => $result['error']])->withInput();
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar el reembolso: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Obtener el estado de pagos del participante (reutilizando el mismo endpoint)
     */
    public function getParticipantStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:programs,id',
            'participant_id' => 'required|exists:participants,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $participant = Participant::findOrFail($request->participant_id);
            $program = Program::findOrFail($request->program_id);
            
            // Calcular montos del participante
            $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];
            
            // Calcular monto ya pagado
            $paidAmount = $this->calculatePaidAmount($participant->id, $program->id);
            $balance = max($totalAmount - $paidAmount, 0);
            
            // Calcular porcentaje de pago
            $paymentPercentage = $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0;
            
            // Determinar estado de pago
            $paymentStatus = $this->determinePaymentStatus($paidAmount, $balance, $totalAmount);
            
            // Obtener resumen de cuotas
            $installmentsSummary = $this->getInstallmentsSummary($participant->id, $program->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'participant' => $participant,
                    'program' => $program,
                    'payment_info' => [
                        'total_amount' => $totalAmount,
                        'paid_amount' => $paidAmount,
                        'balance' => $balance,
                        'payment_percentage' => $paymentPercentage,
                        'payment_status' => $paymentStatus,
                        'installments_summary' => $installmentsSummary
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de pagos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular monto pagado
     */
    private function calculatePaidAmount($participantId, $programId): float
    {
        return Payment::whereHas('order', function ($query) use ($participantId, $programId) {
            $query->where('participant_id', $participantId)
                  ->where('program_id', $programId);
        })
        ->where('status', 'completed')
        ->sum('amount');
    }

    /**
     * Determinar estado de pago
     */
    private function determinePaymentStatus($paidAmount, $balance, $totalAmount): string
    {
        if ($paidAmount == 0) {
            return 'no_payments';
        } elseif ($balance <= 0) {
            return 'fully_paid';
        } else {
            return 'partial_payments';
        }
    }

    /**
     * Obtener resumen de cuotas
     */
    private function getInstallmentsSummary($participantId, $programId): string
    {
        $paidInstallments = Installment::whereHas('installmentPlan.order', function ($query) use ($participantId, $programId) {
            $query->where('participant_id', $participantId)
                  ->where('program_id', $programId);
        })
        ->where('status', 'paid')
        ->count();

        $totalInstallments = Installment::whereHas('installmentPlan.order', function ($query) use ($participantId, $programId) {
            $query->where('participant_id', $participantId)
                  ->where('program_id', $programId);
        })
        ->count();

        return $totalInstallments > 0 ? "{$paidInstallments}/{$totalInstallments}" : "0/0";
    }
}
