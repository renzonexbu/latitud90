<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Institution;
use App\Models\Program;
use App\Models\PaymentMode;
use App\Services\Admin\Programs\CreateProgramService;
use App\Services\Admin\Programs\UpdateProgramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Admin\Programs\BulkProgramsActionRequest;
use App\Models\SalesExecutive;
use App\Services\Admin\Programs\BulkProgramsActionService;

class ProgramController extends Controller
{
    public function __construct(
        private CreateProgramService $createProgramService,
        private UpdateProgramService $updateProgramService,
        private BulkProgramsActionService $bulkProgramsActionService
    ) {}

    public function index(Request $request)
    {
        $programs = Program::with(['paymentMode', 'course.institution', 'course.participants'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('active', $status === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        // Cargar las imágenes y métricas de pagos por curso para cada programa
        $programs->getCollection()->transform(function ($program) {
            $program->images = $program->images;

            // Agregados de curso: total debido por todos los participantes y monto pagado
            $participants = $program->course?->participants ?? collect();
            $activeParticipants = $participants->filter(function ($p) {
                return ($p->pivot->status ?? 'active') !== 'cancelled';
            });

            $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($program) {
                $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? $program->trip_price);
                $adj = (float) ($p->pivot->price_adjustments ?? 0);
                return $carry + round($base + $adj, 2);
            }, 0.0);

            $coursePaidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($program) {
                $q->where('program_id', $program->id);
            })
                ->where('status', 'approved')
                ->sum('amount');
            $coursePaidAmount = round($coursePaidAmount, 2);

            $coursePaymentPercentage = $courseTotalAmount > 0
                ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
                : 0;

            $program->course_total_amount = $courseTotalAmount;
            $program->course_paid_amount = $coursePaidAmount;
            $program->course_payment_percentage = $coursePaymentPercentage;

            return $program;
        });

        // Obtener todos los programas para los filtros (sin paginación)
        $allPrograms = Program::with(['paymentMode', 'course.institution', 'course.participants'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Cargar las imágenes y métricas de pagos por curso para todos los programas
        $allPrograms->transform(function ($program) {
            $program->images = $program->images;

            $participants = $program->course?->participants ?? collect();
            $activeParticipants = $participants->filter(function ($p) {
                return ($p->pivot->status ?? 'active') !== 'cancelled';
            });

            $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($program) {
                $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? $program->trip_price);
                $adj = (float) ($p->pivot->price_adjustments ?? 0);
                return $carry + round($base + $adj, 2);
            }, 0.0);

            $coursePaidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($program) {
                $q->where('program_id', $program->id);
            })
                ->where('status', 'approved')
                ->sum('amount');
            $coursePaidAmount = round($coursePaidAmount, 2);

            $coursePaymentPercentage = $courseTotalAmount > 0
                ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
                : 0;

            $program->course_total_amount = $courseTotalAmount;
            $program->course_paid_amount = $coursePaidAmount;
            $program->course_payment_percentage = $coursePaymentPercentage;

            return $program;
        });

        return Inertia::render('Admin/Programs/Index', [
            'programs' => $programs,
            'allPrograms' => $allPrograms,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        $institutions = Institution::orderBy('name')->get();
        $salesExecutives = SalesExecutive::where('active', true)->orderBy('name')->get(['id', 'name', 'code']);
        // Cargar catálogo de opciones de pago (para futuras mejoras: enviarlo desde backend)
        return Inertia::render('Admin/Programs/Create', [
            'institutions' => $institutions,
            'salesExecutives' => $salesExecutives,
        ]);
    }

    public function store(CreateProgramRequest $request)
    {
        try {
            $program = $this->createProgramService->execute($request->validated());

            return redirect()->route('admin.programs.index')
                ->with('success', 'Programa creado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear el programa: ' . $e->getMessage()]);
        }
    }

    public function show(Program $program)
    {
        $program->load(['course', 'course.participants']);

        return Inertia::render('Admin/Programs/Show', [
            'program' => $program
        ]);
    }

    /**
     * Show program files and images
     */
    public function files(Program $program)
    {
        return response()->json([
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'itinerary_file' => $program->itinerary_file_url,
                'travel_assistance_coverage' => $program->travel_assistance_coverage_url,
                'equipment_list' => $program->equipment_list_url,
                'images' => $program->images,
            ]
        ]);
    }

    public function edit(Program $program)
    {
        // Cargar todas las relaciones necesarias
        $program->load([
            'course.institution',
            'course.participants',
            'paymentMode',
            'totalPaymentMethod',
            'lat90PaymentMethod'
        ]);

        // Métricas de pagos agregadas por curso para la vista de edición
        $participants = $program->course?->participants ?? collect();
        $activeParticipants = $participants->filter(function ($p) {
            return ($p->pivot->status ?? 'active') !== 'cancelled';
        });

        $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($program) {
            $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? $program->trip_price);
            $adj = (float) ($p->pivot->price_adjustments ?? 0);
            return $carry + round($base + $adj, 2);
        }, 0.0);

        $coursePaidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($program) {
            $q->where('program_id', $program->id);
        })
            ->where('status', 'approved')
            ->sum('amount');
        $coursePaidAmount = round($coursePaidAmount, 2);

        $coursePaymentPercentage = $courseTotalAmount > 0
            ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
            : 0;

        $program->course_total_amount = $courseTotalAmount;
        $program->course_paid_amount = $coursePaidAmount;
        $program->course_payment_percentage = $coursePaymentPercentage;

        // Pre-cargar opciones de pago habilitadas (program_payment_option)
        $paymentOptions = \Illuminate\Support\Facades\DB::table('program_payment_option')
            ->join('payment_options', 'payment_options.id', '=', 'program_payment_option.payment_option_id')
            ->where('program_payment_option.program_id', $program->id)
            ->select('payment_options.code', 'payment_options.mode')
            ->get();
        $program->full_payment_options = $paymentOptions->where('mode', 'full')->pluck('code')->values();
        $program->lat90_payment_options = $paymentOptions->where('mode', 'lat90')->pluck('code')->values();

        // Obtener instituciones y ejecutivos para el dropdown
        $institutions = Institution::active()->orderBy('name')->get();
        $salesExecutives = \App\Models\SalesExecutive::where('active', true)->orderBy('name')->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Programs/Edit', [
            'program' => $program,
            'institutions' => $institutions,
            'salesExecutives' => $salesExecutives,
        ]);
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        try {
            Log::info('ProgramController@update: Request method y headers', [
                'program_id' => $program->id,
                'method' => $request->method(),
                'is_method_put' => $request->isMethod('PUT'),
                'is_method_post' => $request->isMethod('POST'),
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
            ]);

            Log::info('ProgramController@update: Request all()', [
                'program_id' => $program->id,
                'all' => $request->all(),
                'input' => $request->input(),
                'files' => $request->allFiles(),
            ]);

            $validated = $request->validated();
            Log::info('ProgramController@update: Datos validados recibidos', [
                'program_id' => $program->id,
                'keys' => array_keys($validated),
                'payment_option' => $validated['payment_option'] ?? null,
                'payment_options' => $validated['payment_options'] ?? null,
                'full_payment_method' => $validated['full_payment_method'] ?? null,
                'installments_payment_method' => $validated['installments_payment_method'] ?? null,
                'max_installments' => $validated['max_installments'] ?? null,
            ]);

            $program = $this->updateProgramService->execute($validated, $program);

            return redirect()->route('admin.programs.index')
                ->with('success', 'Programa actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('ProgramController@update: Error al actualizar programa', [
                'program_id' => $program->id,
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Error al actualizar el programa: ' . $e->getMessage()]);
        }
    }

    public function destroy(Program $program)
    {
        // Eliminar archivos asociados si existen
        if ($program->itinerary_file) {
            Storage::disk('public')->delete($program->itinerary_file);
        }
        if ($program->travel_assistance_coverage) {
            Storage::disk('public')->delete($program->travel_assistance_coverage);
        }
        if ($program->equipment_list) {
            Storage::disk('public')->delete($program->equipment_list);
        }

        $program->delete();

        return redirect()->route('admin.programs.index')
            ->with('success', 'Programa eliminado exitosamente.');
    }

    public function toggleStatus(Program $program)
    {
        $program->update(['active' => !$program->active]);

        return back()->with('success', 'Estado del programa actualizado exitosamente.');
    }

    public function passengers(Program $program)
    {
        $participants = $program->participants()
            ->with(['payments'])
            ->where('status', '!=', 'cancelled')
            ->paginate(10);

        return Inertia::render('Admin/Programs/Passengers', [
            'program' => $program,
            'participants' => $participants,
        ]);
    }

    public function bulkAction(BulkProgramsActionRequest $request)
    {
        $validated = $request->validated();
        $message = $this->bulkProgramsActionService->execute($validated['action'], $validated['program_ids']);
        return back()->with('success', $message);
    }
}
