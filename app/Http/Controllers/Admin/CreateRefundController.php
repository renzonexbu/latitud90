<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Document;
use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Region;
use App\Services\Admin\Payments\CreateRefundService;
use App\Services\Admin\Payments\ImportRefundsService;
use App\Models\Payment;
use App\Models\Installment;
use App\Models\PaymentOption;
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
     * Preview la importación desde Excel WITHOUT inserting into database
     * Shows what would be imported for user confirmation
     */
    public function preview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB máximo
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first()
            ], 422);
        }

        try {
            $file = $request->file('file');

            // Preview el archivo Excel (NO database inserts)
            $result = $this->importRefundsService->previewExcel($file);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'results' => $result['results'],
                    'total_rows' => $result['total_rows'] ?? 0,
                    'previewed_rows' => $result['previewed_rows'] ?? 0,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 422);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
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
        // Obtener programas activos (ProgramCourses) con sus relaciones
        // NOTA: En el nuevo diseño, ProgramCourse es la instancia específica del programa para un curso
        $programCourses = ProgramCourse::with(['program', 'course.participants'])
            ->where('active', true)
            ->get()
            ->map(function ($programCourse) {
                return [
                    'id' => $programCourse->id,
                    'name' => $programCourse->name,
                    'code' => $programCourse->code,
                    'destination' => $programCourse->program->destination ?? null,
                    'trip_price' => $programCourse->trip_price,
                    'departure_date' => $programCourse->departure_date,
                    'course' => [
                        'id' => $programCourse->course->id ?? null,
                        'institution_name' => $programCourse->course->institution->name ?? null,
                        'education_level' => $programCourse->course->education_level ?? null,
                        'grade' => $programCourse->course->grade ?? null,
                        // Incluir lista de participantes para el selector
                        'participants' => $programCourse->course->participants ?? [],
                    ],
                    'participants_count' => $programCourse->course->participants->count() ?? 0,
                ];
            });

        $countries = Country::where('name', 'Chile')->get();
        $regions = Region::with('comunes')->get();
        $documentTypes = Document::all();

        // Obtener opciones de reembolso disponibles (NC y RA)
        $refundOptions = PaymentOption::where('mode', 'refund')
            ->where('active', true)
            ->get()
            ->map(function ($option) {
                return [
                    'code' => $option->code,
                    'label' => $option->label,
                    'report_code' => $option->report_code,
                ];
            });

        return Inertia::render('Admin/Payments/Refunds', [
            'programs' => $programCourses,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes,
            'refundOptions' => $refundOptions
        ]);
    }

    /**
     * Almacenar el reembolso
     */
    public function store(Request $request)
    {
        // Validar datos requeridos
        // NOTA: program_id ahora es el ID de ProgramCourse, no de Program template
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:program_courses,id',
            'participant_id' => 'required|exists:participants,id',
            'sii_code' => 'required|string|max:255',
            'document_number' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'total_amount' => 'required|numeric|min:1',
            'refund_type' => 'required|string|in:refund_credit_note,refund_aporte_credit_note,refund_admin_reversal',

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
                'refund_type' => $request->refund_type,

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
        // NOTA: program_id ahora es el ID de ProgramCourse, no de Program template
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:program_courses,id',
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
            $programCourse = ProgramCourse::with('program')->findOrFail($request->program_id);

            // Calcular montos del participante
            // Usar el precio del ProgramCourse directamente
            $totalAmount = (float) $programCourse->trip_price;

            // Calcular monto ya pagado (usando programCourse->id que se guarda en orders.program_id)
            $paidAmount = $this->calculatePaidAmount($participant->id, $programCourse->id);
            $balance = max($totalAmount - $paidAmount, 0);

            // Calcular porcentaje de pago
            $paymentPercentage = $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0;

            // Determinar estado de pago
            $paymentStatus = $this->determinePaymentStatus($paidAmount, $balance, $totalAmount);

            // Obtener resumen de cuotas
            $installmentsSummary = $this->getInstallmentsSummary($participant->id, $programCourse->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'participant' => $participant,
                    'program' => [
                        'id' => $programCourse->id,
                        'name' => $programCourse->name,
                        'code' => $programCourse->code,
                        'trip_price' => $programCourse->trip_price,
                        'destination' => $programCourse->program->destination ?? null,
                    ],
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

        // Si no hay planes de cuotas, buscar en orders_detail como fallback
        if ($totalInstallments == 0) {
            $orderDetails = OrderDetail::whereHas('order', function ($q) use ($participantId, $programId) {
                $q->where('participant_id', $participantId)
                    ->where('program_id', $programId)
                    // Excluir órdenes presenciales sin sistema de cuotas
                    ->where('total_installments', '!=', 0);
            })->get();

            $totalInstallments = $orderDetails->count();
            $paidInstallments = $orderDetails->where('is_paid', true)->count();
        }

        return $totalInstallments > 0 ? "{$paidInstallments}/{$totalInstallments}" : "0/0";
    }
}
