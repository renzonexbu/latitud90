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
        return Inertia::render('Admin/Courses/Create', [
            'programs' => Program::where('active', true)->get(),
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
        $course->load(['program', 'createdBy', 'participants']);
        
        return Inertia::render('Admin/Courses/Show', [
            'course' => $course,
            'paymentSummary' => $this->paymentService->getPaymentSummary($course),
            'paymentMethods' => $this->paymentService->getPaymentMethodsSummary($course),
            'recentPayments' => $this->paymentService->getRecentPayments($course),
        ]);
    }

    public function edit(Course $course, Request $request)
    {
        $courseData = $this->dataService->getCourseForEdit($course);
        
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $courseData['course'],
            'institution' => $courseData['institution'],
            'program' => $courseData['program'],
            'participants' => $courseData['participants'],
            'programs' => Program::where('active', true)->get(),
            'institutions' => Institution::active()->orderBy('name')->get(),
            'payments' => $this->dataService->getFilteredPayments($course, $request->all()),
            'headerInfo' => $this->dataService->getCourseHeaderInfo($course),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        try {
            $this->courseService->updateCourse($course, $request->validated());
            
            if ($course->program_id) {
                $this->courseService->regenerateProgramName($course);
            }
            
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
