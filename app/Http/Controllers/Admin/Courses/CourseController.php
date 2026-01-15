<?php

namespace App\Http\Controllers\Admin\Courses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Courses\StoreCourseRequest;
use App\Http\Requests\Admin\Courses\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
use App\Services\Admin\Courses\CourseDataService;
use App\Services\Admin\Courses\CoursePaymentService;
use App\Services\Admin\Courses\CourseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function __construct(
        private CourseService $courseService,
        private CourseDataService $dataService,
        private CoursePaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $courses = $this->dataService->getCourseList($request->all());
        $courses = $this->dataService->calculateCourseMetrics($courses);

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'allCourses' => $courses->getCollection(),
            'filters' => $request->only(['search', 'status']),
            'programs' => Program::where('active', true)->get(),
            'institutions' => Institution::active()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        // Obtener plantillas de programas activas
        $programs = Program::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'destination', 'images_folder']);

        // Obtener instituciones activas
        $institutions = Institution::active()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        // Obtener ejecutivos de ventas
        $salesExecutives = \App\Models\SalesExecutive::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Courses/Create', [
            'programs' => $programs,
            'institutions' => $institutions,
            'salesExecutives' => $salesExecutives,
        ]);
    }

    public function store(StoreCourseRequest $request)
    {
        try {
            $course = $this->courseService->createCourse($request->validated());

            return redirect()
                ->route('admin.courses.index')
                ->with('success', 'Curso creado exitosamente');

        } catch (\App\Exceptions\ParticipantImportException $e) {
            // Manejar errores de importación de participantes
            return back()
                ->withInput()
                ->withErrors([
                    'studentsFile' => $e->getMessage(),
                    'importErrors' => $e->getErrors(),
                ])
                ->with('importErrorDetails', $e->toArray());
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al crear el curso: ' . $e->getMessage()]);
        }
    }

    public function show(Course $course)
    {
        $course->load(['institution', 'createdBy', 'participants', 'programCourses.program']);

        return Inertia::render('Admin/Courses/Show', [
            'course' => $course,
            'paymentSummary' => $this->paymentService->getPaymentSummary($course),
            'paymentMethods' => $this->paymentService->getPaymentMethodsSummary($course),
            'recentPayments' => $this->paymentService->getRecentPayments($course),
        ]);
    }

    public function edit(Course $course, Request $request)
    {
        // Load course with necessary relationships, including paymentOptions
        $course->load(['institution', 'programCourses.program', 'programCourses.paymentOptions']);

        // Debug: Log the programCourse file data
        $programCourse = $course->programCourses->first();
        if ($programCourse) {
            \Illuminate\Support\Facades\Log::info('Edit Course - ProgramCourse Files', [
                'program_course_id' => $programCourse->id,
                'itinerary_file' => $programCourse->itinerary_file,
                'itinerary_file_url' => $programCourse->itinerary_file_url,
                'travel_assistance_coverage' => $programCourse->travel_assistance_coverage,
                'travel_assistance_coverage_url' => $programCourse->travel_assistance_coverage_url,
                'equipment_list' => $programCourse->equipment_list,
                'equipment_list_url' => $programCourse->equipment_list_url,
            ]);
        }

        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course,
            'programs' => Program::where('active', true)->get(),
            'institutions' => Institution::active()->orderBy('name')->get(),
            'salesExecutives' => \App\Models\SalesExecutive::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        try {
            $this->courseService->updateCourse($course, $request->validated());

            return redirect()
                ->route('admin.courses.edit', $course)
                ->with('success', 'Curso actualizado exitosamente');

        } catch (\App\Exceptions\ParticipantImportException $e) {
            // Manejar errores de importación de participantes
            return back()
                ->withInput()
                ->withErrors([
                    'studentsFile' => $e->getMessage(),
                    'importErrors' => $e->getErrors(),
                ])
                ->with('importErrorDetails', $e->toArray());
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al actualizar el curso: ' . $e->getMessage()]);
        }
    }

    /**
     * Get participants for a specific program-course
     */
    public function getParticipants(Request $request)
    {
        $programCourseId = $request->input('program_course_id');

        if (!$programCourseId) {
            return response()->json(['error' => 'program_course_id es requerido'], 400);
        }

        $participants = $this->courseService->getParticipantsWithPaymentStatus($programCourseId);

        return response()->json([
            'participants' => $participants
        ]);
    }

    /**
     * Remove a participant from a program-course
     * Only allows deletion if participant has NO payments
     */
    public function removeParticipant(Request $request)
    {
        $request->validate([
            'participant_program_id' => 'required|exists:participant_program,id',
        ]);

        try {
            $result = $this->courseService->removeParticipant($request->participant_program_id);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar participante: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Course $course)
    {
        try {
            $this->courseService->deleteCourse($course);

            return redirect()
                ->route('admin.courses.index')
                ->with('success', 'Curso eliminado exitosamente');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al eliminar el curso: ' . $e->getMessage()]);
        }
    }

    public function toggleStatus(Course $course)
    {
        try {
            $success = $this->courseService->toggleStatus($course);

            if (!$success) {
                throw new \Exception('No se pudo cambiar el estado del curso');
            }

            return redirect()
                ->route('admin.courses.index')
                ->with('success', 'Estado del curso actualizado exitosamente');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al actualizar el estado del curso: ' . $e->getMessage()]);
        }
    }

    /**
     * Verificar breakdown de pagos para un curso (API endpoint para debugging)
     */
    public function verifyPaymentBreakdown(Course $course)
    {
        $course->load(['programCourses']);

        $programCourse = $course->programCourses->first();
        $programId = $programCourse?->program_id;

        if (!$programId) {
            return response()->json([
                'error' => 'No se encontró programa asociado al curso',
                'course_id' => $course->id,
                'course_name' => $course->course_display,
            ]);
        }

        // Obtener pagos normales completados
        $normalPayments = \App\Models\Payment::with(['order.participant'])
            ->whereHas('order', function($q) use ($programId) {
                $q->where('program_id', $programId);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'participant' => $payment->order->participant->full_name ?? 'N/A',
                    'created_at' => $payment->created_at->format('Y-m-d H:i:s'),
                ];
            });

        $normalPaymentsTotal = $normalPayments->sum('amount');

        // Obtener cuotas de suscripciones pagadas
        $subscriptionInstallments = \App\Models\Installment::with(['installmentPlan.participant'])
            ->whereHas('installmentPlan', function($q) use ($programId) {
                $q->where('program_id', $programId);
            })
            ->where('is_paid', true)
            ->get()
            ->map(function($installment) {
                return [
                    'id' => $installment->id,
                    'installment_number' => $installment->installment_number,
                    'amount' => $installment->amount,
                    'is_paid' => $installment->is_paid,
                    'participant' => $installment->installmentPlan->participant->full_name ?? 'N/A',
                    'due_date' => $installment->due_date,
                    'paid_at' => $installment->paid_at?->format('Y-m-d H:i:s'),
                ];
            });

        $subscriptionPaymentsTotal = $subscriptionInstallments->sum('amount');

        // Obtener totales de suscripciones
        $subscriptionPlans = \App\Models\InstallmentPlan::with(['participant'])
            ->where('program_id', $programId)
            ->whereIn('status', ['active', 'pending', 'completed'])
            ->get()
            ->map(function($plan) {
                return [
                    'id' => $plan->id,
                    'participant' => $plan->participant->full_name ?? 'N/A',
                    'total_amount' => $plan->total_amount,
                    'status' => $plan->status,
                    'total_installments' => $plan->total_installments,
                ];
            });

        $subscriptionPlansTotal = $subscriptionPlans->sum('total_amount');

        return response()->json([
            'course' => [
                'id' => $course->id,
                'name' => $course->course_display,
                'institution' => $course->institution->name ?? 'N/A',
                'program_id' => $programId,
            ],
            'normal_payments' => [
                'count' => $normalPayments->count(),
                'total' => $normalPaymentsTotal,
                'details' => $normalPayments,
            ],
            'subscription_installments_paid' => [
                'count' => $subscriptionInstallments->count(),
                'total' => $subscriptionPaymentsTotal,
                'details' => $subscriptionInstallments,
            ],
            'subscription_plans' => [
                'count' => $subscriptionPlans->count(),
                'total' => $subscriptionPlansTotal,
                'details' => $subscriptionPlans,
            ],
            'summary' => [
                'total_paid' => $normalPaymentsTotal + $subscriptionPaymentsTotal,
                'total_expected' => $subscriptionPlansTotal, // + participants total (not included here)
            ],
        ]);
    }
}
