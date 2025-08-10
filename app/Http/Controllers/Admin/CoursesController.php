<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateCourseRequest;
use App\Models\Course;
use App\Models\Institution;
use App\Models\Program;
use App\Services\Admin\Courses\CreateCourseService;
use App\Services\Admin\Courses\EditCourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Requests\Admin\Courses\UpdateCourseRequest;

class CoursesController extends Controller
{
    protected $createCourseService;
    protected $editCourseService;

    public function __construct(CreateCourseService $createCourseService, EditCourseService $editCourseService)
    {
        $this->createCourseService = $createCourseService;
        $this->editCourseService = $editCourseService;
    }

    public function index(Request $request)
    {
        $courses = Course::with(['program', 'createdBy', 'institution'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('institution', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                ->orWhere('education_level', 'like', "%{$search}%")
                ->orWhere('grade', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Asegurar que los programas se carguen con todos los campos necesarios
        $courses->getCollection()->transform(function ($course) {
            if ($course->program) {
                $course->program->makeVisible(['trip_price', 'name', 'destination']);
            }
            // Agregar los accessors calculados
            $course->append(['payment_percentage', 'payment_percentage_text']);
            return $course;
        });

        // Obtener todos los cursos para los filtros (sin paginación)
        $allCourses = Course::with(['program', 'createdBy', 'institution'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Aplicar las mismas transformaciones a todos los cursos
        $allCourses->transform(function ($course) {
            if ($course->program) {
                $course->program->makeVisible(['trip_price', 'name', 'destination']);
            }
            // Agregar los accessors calculados
            $course->append(['payment_percentage', 'payment_percentage_text']);
            return $course;
        });

        $programs = Program::where('active', true)->get();
        $institutions = Institution::active()->orderBy('name')->get();
        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
            'allCourses' => $allCourses,
            'filters' => $request->only(['search', 'status']),
            'programs' => $programs,
            'institutions' => $institutions,
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
            
            // Preparar los datos para el servicio
            $courseData = [
                'institutionId' => $validatedData['institutionId'],
                'educationLevel' => $validatedData['educationLevel'],
                'year' => $validatedData['year'],
                'grade' => $validatedData['grade'],
                'shift' => $validatedData['shift'],
                'contactEmail' => $validatedData['contactEmail'],
                'contactPhone' => $validatedData['contactPhone'],
                'endDate' => $validatedData['endDate'] ?? null,
                'studentsFile' => $request->file('students_file') ?? null,
            ];
            
            $course = $this->createCourseService->execute($courseData);
            
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
        $courseData = $this->editCourseService->execute($course->id);
        $headerInfo = $this->editCourseService->getCourseHeaderInfo($course);
        $programs = Program::where('active', true)->get();
        $institutions = Institution::active()->orderBy('name')->get();

        return Inertia::render('Admin/Courses/Edit', [
            'course' => $courseData['course'],
            'institution' => $courseData['institution'],
            'program' => $courseData['program'],
            'participants' => $courseData['participants'],
            'headerInfo' => $headerInfo,
            'programs' => $programs,
            'institutions' => $institutions,
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course)
    {
        try {
            $validatedData = $request->validated();
            
            // Actualizar el curso
            $course->update([
                'institution_id' => $validatedData['institutionId'],
                'education_level' => $validatedData['educationLevel'],
                'year' => $validatedData['year'],
                'grade' => $validatedData['grade'],
                'shift' => $validatedData['shift'],
                'contact_email' => $validatedData['contactEmail'],
                'contact_phone' => $validatedData['contactPhone'],
                'program_id' => $validatedData['associatedProgram'],
                'end_date' => $validatedData['endDate'],
            ]);
            
            return redirect()->route('admin.courses.edit', $course)
                ->with('success', 'Curso actualizado exitosamente.');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar el curso: ' . $e->getMessage()]);
        }
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
