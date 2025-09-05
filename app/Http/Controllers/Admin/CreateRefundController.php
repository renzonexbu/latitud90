<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Region;
use App\Services\Admin\Payments\CreateRefundService;
use App\Services\Admin\Payments\ImportRefundsService;
use App\Models\Payment;
use App\Models\Installment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CreateRefundController extends Controller
{
    public function __construct(
        private CreateRefundService $createRefundService,
        private ImportRefundsService $importRefundsService
    ) {}

    /**
     * Mostrar el menú de opciones para devoluciones
     */
    public function menu()
    {
        return Inertia::render('Admin/Payments/RefundsMenu');
    }

    /**
     * Mostrar el formulario de importación desde Excel
     */
    public function import()
    {
        return Inertia::render('Admin/Payments/RefundsImport');
    }

    /**
     * Procesar la importación desde Excel
     */
    public function importStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB máximo
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $file = $request->file('file');

            // Procesar el archivo Excel
            $result = $this->importRefundsService->processExcel($file);

            if ($result['success']) {
                $successCount = $result['results']['successful'];
                $totalCount = $result['results']['processed'];
                
                return back()->with('success', "Importación exitosa: {$successCount} de {$totalCount} devoluciones procesadas correctamente.");
            } else {
                return back()->withErrors(['error' => $result['error']])->withInput();
            }

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al procesar el archivo: ' . $e->getMessage()])->withInput();
        }
    }


    /**
     * Mostrar el formulario de creación de reembolso manual
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
            'sii_code' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'total_amount' => 'required|numeric|min:1',
            
            // Validar datos del cliente
            'client_rut' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Preparar datos para el servicio
            $refundData = [
                'program_id' => $request->program_id,
                'participant_id' => $request->participant_id,
                'amount' => $request->total_amount,
                'transaction_date' => $request->transaction_date,
                'payment_code' => $request->sii_code,
                
                // Datos fiscales
                'sii_code' => $request->sii_code,
                'document_number' => $request->document_number,
                'total_amount' => $request->total_amount,
                
                // Datos del cliente
                'client_rut' => $request->client_rut,
                'client_name' => $request->client_name,
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
        ->whereIn('status', ['approved', 'completed'])
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
