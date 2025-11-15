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
        // Load course with necessary relationships
        $course->load(['institution', 'programCourses.program']);

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

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Error al actualizar el curso: ' . $e->getMessage()]);
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
}
