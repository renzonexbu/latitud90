<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Payments\CreateParticularPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CreateParticularPaymentController extends Controller
{
    public function __construct(
        private CreateParticularPaymentService $createParticularPaymentService
    ) {}

    /**
     * Mostrar el formulario de creación de pago presencial
     */
    public function create()
    {
        // Obtener datos necesarios para el formulario
        $programs = \App\Models\Program::with(['course.participants'])->where('active', true)->get();
        $countries = \App\Models\Country::where('name', 'Chile')->get();
        $regions = \App\Models\Region::with('comunes')->get();
        $documentTypes = \App\Models\Document::all();
        
        return Inertia::render('Admin/Payments/Create', [
            'programs' => $programs,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes
        ]);
    }

    /**
     * Almacenar el pago presencial
     */
    public function store(Request $request)
    {
        // Validar datos del formulario
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:programs,id',
            'participant_id' => 'required|exists:participants,id',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'payment_code' => 'required|string|max:255',
            'authorization_code' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            
            // Datos del comprador
            'buyer_full_name' => 'required|string|max:255',
            'buyer_document_type' => 'required|exists:document,id',
            'buyer_document_number' => 'required|string|max:255',
            'buyer_email' => 'required|email|max:255',
            'buyer_phone' => 'required|string|max:255',
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
            $paymentData = [
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
            $result = $this->createParticularPaymentService->execute($paymentData);

            if ($result['success']) {
                return redirect()->route('admin.payments.index')
                    ->with('success', 'Pago presencial registrado exitosamente. Se han reestructurado las cuotas pendientes.');
            } else {
                return back()->withErrors(['error' => $result['error']])->withInput();
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Obtener el estado de pagos del participante
     */
    public function getParticipantPaymentStatus(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'participant_id' => 'required|exists:participants,id',
        ]);

        $programId = $request->program_id;
        $participantId = $request->participant_id;

        try {
            // Obtener el participante
            $participant = \App\Models\Participant::find($participantId);
            $program = \App\Models\Program::find($programId);

            if (!$participant || !$program) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participante o programa no encontrado'
                ], 404);
            }

            // Calcular montos usando el helper
            $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
            $totalAmount = $priceData['final_price'];

            // Buscar planes de cuotas del participante para este programa
            $installmentPlans = \App\Models\InstallmentPlan::where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->with(['installments'])
                ->get();

            // Calcular cuotas pagadas y total de cuotas desde la tabla installments
            $totalInstallments = 0;
            $paidInstallments = 0;
            $totalPaidAmount = 0;

            foreach ($installmentPlans as $plan) {
                $totalInstallments += $plan->total_installments;
                
                foreach ($plan->installments as $installment) {
                    if ($installment->status === 'paid') {
                        $paidInstallments++;
                        $totalPaidAmount += $installment->amount;
                    }
                }
            }

            // Si no hay planes de cuotas, buscar en orders_detail como fallback
            if ($totalInstallments == 0) {
                $orderDetails = \App\Models\OrderDetail::whereHas('order', function ($q) use ($participantId, $programId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programId);
                })->get();

                $totalInstallments = $orderDetails->count();
                $paidInstallments = $orderDetails->where('is_paid', true)->count();
                $totalPaidAmount = $orderDetails->where('is_paid', true)->sum('amount');
            }

            $balance = max($totalAmount - $totalPaidAmount, 0);
            $paymentPercentage = $totalAmount > 0 ? round(($totalPaidAmount / $totalAmount) * 100, 0) : 0;

            // Determinar estado del pago
            $paymentStatus = $this->determinePaymentStatus($totalInstallments, $paidInstallments, $totalPaidAmount, $totalAmount);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_info' => [
                        'total_amount' => $totalAmount,
                        'paid_amount' => $totalPaidAmount,
                        'balance' => $balance,
                        'payment_percentage' => $paymentPercentage,
                        'total_installments' => $totalInstallments,
                        'paid_installments' => $paidInstallments,
                        'pending_installments' => $totalInstallments - $paidInstallments,
                        'installments_summary' => "{$paidInstallments}/{$totalInstallments}",
                        'payment_status' => $paymentStatus
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
     * Determinar el estado del pago
     */
    private function determinePaymentStatus(int $totalInstallments, int $paidInstallments, float $paidAmount, float $totalAmount): string
    {
        if ($totalInstallments == 0) {
            return 'no_enrolled';
        }

        if ($paidAmount == 0) {
            return 'no_payments';
        }

        if ($paidAmount >= $totalAmount) {
            return 'fully_paid';
        }

        return 'partial_payments';
    }
}
