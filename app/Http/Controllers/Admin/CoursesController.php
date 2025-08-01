<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCourseRequest;
use App\Models\Course;
use App\Models\Program;
use App\Services\Admin\Courses\CreateCourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CoursesController extends Controller
{
    protected $createCourseService;

    public function __construct(CreateCourseService $createCourseService)
    {
        $this->createCourseService = $createCourseService;
    }

    public function index(Request $request)
    {
        $courses = Course::with(['program', 'createdBy'])
            ->when($request->search, function ($query, $search) {
                $query->where('institution_name', 'like', "%{$search}%")
                    ->orWhere('education_level', 'like', "%{$search}%")
                    ->orWhere('grade', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $programs = Program::where('active', true)->get();
        
        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'filters' => $request->only(['search', 'status']),
            'programs' => $programs,
        ]);
    }

    public function create()
    {
        $programs = Program::where('active', true)->get();
        
        return Inertia::render('Admin/Courses/Create', [
            'programs' => $programs,
        ]);
    }

    public function store(CreateCourseRequest $request)
    {
        try {
            $validatedData = $request->validated();
            
            $course = $this->createCourseService->execute($validatedData);
            
            return redirect()->route('admin.courses.index')
                ->with('success', 'Curso creado exitosamente.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear el curso: ' . $e->getMessage()]);
        }
    }

    public function show(Course $course)
    {
        $course->load(['program', 'createdBy', 'participants']);
        
        return Inertia::render('Admin/Courses/Show', [
            'course' => $course
        ]);
    }

    public function edit(Course $course)
    {
        $programs = Program::where('active', true)->get();
        
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course,
            'programs' => $programs,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        // Por ahora solo redirigimos al index
        // Aquí se implementará la lógica de actualización cuando esté listo
        return redirect()->route('admin.courses.index')
            ->with('success', 'Curso actualizado exitosamente.');
    }

    public function destroy(Course $course)
    {
        try {
            // Delete associated file if exists
            if ($course->students_file_path) {
                Storage::disk('public')->delete($course->students_file_path);
            }
            
            $course->delete();
            
            return redirect()->route('admin.courses.index')
                ->with('success', 'Curso eliminado exitosamente.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar el curso: ' . $e->getMessage()]);
        }
    }

    public function toggleStatus(Course $course)
    {
        try {
            $course->update([
                'status' => $course->status === 'active' ? 'inactive' : 'active'
            ]);
            
            return redirect()->route('admin.courses.index')
                ->with('success', 'Estado del curso actualizado exitosamente.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar el estado del curso: ' . $e->getMessage()]);
        }
    }
}
