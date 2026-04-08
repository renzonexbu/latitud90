<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Document;
use App\Models\Participant;
use App\Models\InstallmentPlan;
use App\Models\OrderDetail;
use App\Models\Program;
use App\Models\ProgramCourse;
use App\Models\Region;
use App\Helpers\PaymentDocumentTypeHelper;
use App\Services\Admin\Payments\CreateParticularPaymentService;
use App\Services\Admin\Installments\RegisterManualPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class CreateParticularPaymentController extends Controller
{
    public function __construct(
        private CreateParticularPaymentService $createParticularPaymentService
    ) {}

    /**
     * Mostrar el menú de opciones para pagos presenciales
     */
    public function menu()
    {
        return Inertia::render('Admin/Payments/PresentialPaymentsMenu');
    }

    /**
     * Mostrar el formulario de creación de pago presencial
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
        $paymentTypeOptions = RegisterManualPaymentService::getPaymentSourceOptions();

        return Inertia::render('Admin/Payments/Create', [
            'programs' => $programCourses,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes,
            'paymentTypeOptions' => $paymentTypeOptions,
            'fiscalDocumentTypes' => PaymentDocumentTypeHelper::getSelectableDocumentTypes(),
        ]);
    }

    /**
     * Almacenar el pago presencial
     */
    public function store(Request $request)
    {
        // Validar datos del formulario
        // NOTA: program_id ahora es el ID de ProgramCourse, no de Program template
        $validator = Validator::make($request->all(), [
            'program_id' => 'required|exists:program_courses,id',
            'participant_id' => 'required|exists:participants,id',
            'amount' => 'required|numeric|min:0',
            'transaction_date' => 'required|date',
            'authorization_code' => 'required|string|max:255',
            'installments' => 'required|integer|min:1|max:36',
            'notes' => 'nullable|string',
            'presential_payment_type' => 'required|in:TC,KP,PAT,TE,VP,VPI,DP,WP,AP,CT',

            // Datos del comprador
            'buyer_first_name' => 'required|string|max:255',
            'buyer_last_name' => 'required|string|max:255',
            'buyer_document_type' => 'required|exists:document,id',
            'buyer_document_number' => 'required|string|max:255',
            'buyer_email' => 'nullable|email|max:255',
            'fiscal_document_type' => 'nullable|in:B2,FF',
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
                'authorization_code' => $request->authorization_code,
                'installments' => $request->installments,
                'notes' => $request->notes,
                'presential_payment_type' => $request->presential_payment_type,

                // Datos del comprador
                'buyer_first_name' => $request->buyer_first_name,
                'buyer_last_name' => $request->buyer_last_name,
                'buyer_full_name' => trim($request->buyer_first_name . ' ' . $request->buyer_last_name),
                'buyer_document_type' => $request->buyer_document_type,
                'buyer_document_number' => $request->buyer_document_number,
                'buyer_email' => $request->buyer_email,
                'buyer_phone' => $request->buyer_phone,
                'buyer_code_phone' => $request->buyer_code_phone,
                'buyer_country' => $request->buyer_country,
                'buyer_region' => $request->buyer_region,
                'buyer_city' => $request->buyer_city,
                'fiscal_document_type' => $request->fiscal_document_type,
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
        // NOTA: program_id ahora es el ID de ProgramCourse, no de Program template
        $request->validate([
            'program_id' => 'required|exists:program_courses,id',
            'participant_id' => 'required|exists:participants,id',
        ]);

        $programId = $request->program_id;
        $participantId = $request->participant_id;

        try {
            // Obtener el participante y el ProgramCourse
            $participant = Participant::find($participantId);
            $programCourse = ProgramCourse::with('program')->find($programId);

            if (!$participant || !$programCourse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Participante o programa no encontrado'
                ], 404);
            }

            // Calcular montos usando el precio del ProgramCourse directamente
            $totalAmount = (float) $programCourse->trip_price;

            // Buscar planes de cuotas del participante para este programa
            $installmentPlans = InstallmentPlan::where('participant_id', $participantId)
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
                $orderDetails = OrderDetail::whereHas('order', function ($q) use ($participantId, $programId) {
                    $q->where('participant_id', $participantId)
                        ->where('program_id', $programId)
                        // Excluir órdenes presenciales sin sistema de cuotas
                        ->where('total_installments', '!=', 0);
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

    /**
     * Obtener las opciones de tipo de pago disponibles
     * Devuelve los códigos de reporte y etiquetas para el selector
     */
    public function getPaymentTypeOptions()
    {
        return response()->json([
            'success' => true,
            'data' => RegisterManualPaymentService::getPaymentSourceOptions()
        ]);
    }

    /**
     * Buscar participantes inscritos en programas activos
     * Permite buscar por RUT, nombre, apellido o código de programa
     */
    public function searchEnrolledParticipants(Request $request)
    {
        $search = $request->input('search', '');

        if (strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        try {
            // Limpiar el término de búsqueda (eliminar puntos y guiones para RUT)
            $searchClean = preg_replace('/[.\-]/', '', $search);
            $searchLower = strtolower($search);

            // Buscar participantes que estén inscritos en cursos con programas
            // Incluye todos los programas (activos e inactivos) para poder registrar pagos pendientes
            $results = \Illuminate\Support\Facades\DB::table('participants as p')
                ->join('participant_course as pc', 'p.id', '=', 'pc.participant_id')
                ->join('courses as c', 'c.id', '=', 'pc.course_id')
                ->join('program_courses as pgc', 'pgc.course_id', '=', 'c.id')
                ->leftJoin('participant_program as pp', function ($join) {
                    $join->on('pp.participant_id', '=', 'p.id')
                        ->on('pp.program_id', '=', 'pgc.id');
                })
                ->where(function ($query) use ($search, $searchClean, $searchLower) {
                    // Buscar por documento (con o sin formato)
                    $query->where('p.document_number', 'LIKE', "%{$search}%")
                        ->orWhere(\Illuminate\Support\Facades\DB::raw("REPLACE(REPLACE(p.document_number, '.', ''), '-', '')"), 'LIKE', "%{$searchClean}%")
                        // Buscar por nombre
                        ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(p.first_name)'), 'LIKE', "%{$searchLower}%")
                        ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(p.second_name)'), 'LIKE', "%{$searchLower}%")
                        // Buscar por apellido
                        ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(p.first_last_name)'), 'LIKE', "%{$searchLower}%")
                        ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(p.second_last_name)'), 'LIKE', "%{$searchLower}%")
                        // Buscar por código de programa
                        ->orWhere(\Illuminate\Support\Facades\DB::raw('LOWER(pgc.code)'), 'LIKE', "%{$searchLower}%");
                })
                ->select([
                    'p.id as participant_id',
                    'p.first_name',
                    'p.second_name',
                    'p.first_last_name',
                    'p.second_last_name',
                    'p.document_number',
                    'pgc.id as program_course_id',
                    'pgc.code as program_code',
                    'pgc.name as program_name',
                    'pgc.active as program_active',
                    'pp.enrollment_code',
                    'pp.is_active as enrollment_active',
                ])
                ->orderBy('p.first_last_name')
                ->orderBy('p.first_name')
                ->limit(20)
                ->get();

            // Formatear resultados
            $formattedResults = $results->map(function ($item) {
                // Formatear nombre completo
                $fullName = trim(
                    ucfirst(strtolower($item->first_name ?? '')) . ' ' .
                    ucfirst(strtolower($item->first_last_name ?? ''))
                );

                // Formatear RUT si es RUT chileno
                $document = $item->document_number;
                $cleanDoc = preg_replace('/[.\-]/', '', $document ?? '');
                if (preg_match('/^\d{7,8}[\dkK]$/i', $cleanDoc)) {
                    $body = substr($cleanDoc, 0, -1);
                    $dv = strtoupper(substr($cleanDoc, -1));
                    $document = number_format((int)$body, 0, '', '.') . '-' . $dv;
                }

                return [
                    'participant_id' => $item->participant_id,
                    'program_course_id' => $item->program_course_id,
                    'full_name' => $fullName,
                    'document_number' => $document,
                    'program_code' => $item->program_code,
                    'program_name' => $item->program_name,
                    'enrollment_code' => $item->enrollment_code,
                    'enrollment_active' => $item->enrollment_active ?? true,
                    'program_active' => (bool) $item->program_active,
                    // Label para mostrar en el dropdown
                    'label' => "{$document} - {$fullName} ({$item->program_code})",
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedResults
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error buscando participantes inscritos', [
                'search' => $search,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar participantes: ' . $e->getMessage()
            ], 500);
        }
    }
}
