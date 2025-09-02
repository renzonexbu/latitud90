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
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Http\Requests\Admin\Courses\UpdateCourseRequest;
use App\Helpers\ParticipantPriceHelper;
use App\Models\Payment;

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
                    ->orWhere('course_name', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Asegurar que los programas se carguen con todos los campos necesarios y métricas de pago agregadas
        $courses->getCollection()->transform(function ($course) {
            if ($course->program) {
                $course->program->makeVisible(['trip_price', 'name', 'destination']);
            }

            // Total del curso = suma de (precio individual + ajustes) de todos los participantes activos
            $participants = $course->participants ?? collect();
            $activeParticipants = $participants->filter(function ($p) {
                return ($p->pivot->status ?? 'active') !== 'cancelled';
            });
            $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($course) {
                // Usar el helper para calcular el precio final con descuentos
                if ($course->program) {
                    $priceData = ParticipantPriceHelper::calculateParticipantPrice($p, $course->program);
                    return $carry + $priceData['final_price'];
                }
                // Fallback si no hay programa
                $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? 0);
                $adj = (float) ($p->pivot->price_adjustments ?? 0);
                return $carry + round($base + $adj, 2);
            }, 0.0);

            // Monto pagado = suma de pagos aprobados asociados al programa del curso
            $coursePaidAmount = 0.0;
            if ($course->program) {
                $coursePaidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($course) {
                    $q->where('program_id', $course->program->id);
                })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');
            }
            $coursePaidAmount = round($coursePaidAmount, 2);

            $coursePaymentPercentage = $courseTotalAmount > 0
                ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
                : 0;

            // Adjuntar métricas agregadas para la tabla
            $course->course_total_amount = $courseTotalAmount;
            $course->course_paid_amount = $coursePaidAmount;
            $course->course_payment_percentage = $coursePaymentPercentage;

            // Agregar los accessors existentes (por compatibilidad)
            $course->append(['payment_percentage', 'payment_percentage_text']);
            return $course;
        });

        // Obtener todos los cursos para los filtros (sin paginación)
        $allCourses = Course::with(['program', 'createdBy', 'institution'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Aplicar las mismas transformaciones a todos los cursos (incluyendo métricas agregadas)
        $allCourses->transform(function ($course) {
            if ($course->program) {
                $course->program->makeVisible(['trip_price', 'name', 'destination']);
            }

            $participants = $course->participants ?? collect();
            $activeParticipants = $participants->filter(function ($p) {
                return ($p->pivot->status ?? 'active') !== 'cancelled';
            });
            $courseTotalAmount = $activeParticipants->reduce(function ($carry, $p) use ($course) {
                // Usar el helper para calcular el precio final con descuentos
                if ($course->program) {
                    $priceData = ParticipantPriceHelper::calculateParticipantPrice($p, $course->program);
                    return $carry + $priceData['final_price'];
                }
                // Fallback si no hay programa
                $base = (float) ($p->pivot->individual_price ?? $p->individual_price ?? 0);
                $adj = (float) ($p->pivot->price_adjustments ?? 0);
                return $carry + round($base + $adj, 2);
            }, 0.0);

            $coursePaidAmount = 0.0;
            if ($course->program) {
                $coursePaidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($course) {
                    $q->where('program_id', $course->program->id);
                })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');
            }
            $coursePaidAmount = round($coursePaidAmount, 2);

            $coursePaymentPercentage = $courseTotalAmount > 0
                ? round(($coursePaidAmount / $courseTotalAmount) * 100, 0)
                : 0;

            $course->course_total_amount = $courseTotalAmount;
            $course->course_paid_amount = $coursePaidAmount;
            $course->course_payment_percentage = $coursePaymentPercentage;

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
                'grade' => $validatedData['grade'] ?? null,
                'year' => $validatedData['year'],
                'courseNumber' => $validatedData['courseNumber'] ?? ($request->input('course_number') ?? null),
                'courseName' => $validatedData['courseName'] ?? ($request->input('course_name') ?? null),
                'contactEmail' => $validatedData['contactEmail'] ?? null,
                'contactPhone' => $validatedData['contactPhone'] ?? null,
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

    public function edit(Course $course, Request $request)
    {
        $courseData = $this->editCourseService->execute($course->id);
        $headerInfo = $this->editCourseService->getCourseHeaderInfo($course);
        $programs = Program::where('active', true)->get();
        $institutions = Institution::active()->orderBy('name')->get();

        // Obtener pagos relacionados al curso con filtros aplicados
        $paymentsQuery = Payment::with([
            'order.program.course.institution', 
            'order.participant.documentType',
            'orderDetail.country',
            'orderDetail.region', 
            'orderDetail.city',
            'paymentGateway',
            'paymentOption'
        ])
            ->whereHas('order.program.course', function ($query) use ($course) {
                $query->where('id', $course->id);
            });



        // Aplicar filtros de pagos
        if ($request->filled('participant_name')) {
            $paymentsQuery->whereHas('order.participant', function ($query) use ($request) {
                $query->where('first_name', 'like', '%' . $request->participant_name . '%')
                      ->orWhere('last_name', 'like', '%' . $request->participant_name . '%');
            });
        }

        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $paymentsQuery->where('status', $request->payment_status);
        }

        if ($request->filled('program_id')) {
            $paymentsQuery->whereHas('order.program', function ($query) use ($request) {
                $query->where('id', $request->program_id);
            });
        }

        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            // Lógica especial para pagos presenciales (incluye pagos sin payment_option_id)
            if ($request->payment_method === 'presencial') {
                $paymentsQuery->where(function ($query) {
                    $query->whereHas('paymentOption', function ($subQuery) {
                        $subQuery->where('gateway_code', 'presencial');
                    })->orWhereNull('payment_option_id');
                });
            } else {
                // Para otros métodos de pago, usar la lógica normal
                $paymentsQuery->whereHas('paymentOption', function ($query) use ($request) {
                    $query->where('gateway_code', $request->payment_method);
                });
            }
        }

        if ($request->filled('date_from')) {
            $paymentsQuery->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $paymentsQuery->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $paymentsQuery->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'page', $request->get('page', 1));

        return Inertia::render('Admin/Courses/Edit', [
            'course' => $courseData['course'],
            'institution' => $courseData['institution'],
            'program' => $courseData['program'],
            'participants' => $courseData['participants'],
            'headerInfo' => $headerInfo,
            'programs' => $programs,
            'institutions' => $institutions,
            'payments' => $payments,
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
                'grade' => $validatedData['grade'] ?? null,
                'year' => $validatedData['year'],
                'course_number' => $validatedData['courseNumber'] ?? null,
                'course_name' => $validatedData['courseName'] ?? null,
                'contact_email' => $validatedData['contactEmail'] ?? $course->contact_email,
                'contact_phone' => $validatedData['contactPhone'] ?? $course->contact_phone,
                'program_id' => $validatedData['associatedProgram'],
                'end_date' => $validatedData['endDate'],
            ]);

            // Si el curso tiene un programa asociado, regenerar el nombre del programa
            if ($course->program_id) {
                $this->regenerateProgramName($course);
            }

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

    /**
     * Regenerate program name when course data changes.
     */
    private function regenerateProgramName(Course $course): void
    {
        try {
            $program = $course->program;
            if (!$program) {
                return;
            }

            // Construir el nuevo nombre del programa basado en los datos del curso
            $institutionName = $course->institution?->name;
            $level = $this->mapEducationLevel($course->education_level);
            $num = $course->course_number;
            $grade = $course->grade;

            // Construir la parte del curso
            $coursePart = '';
            if ($num) {
                $coursePart = $num . '° ' . $level;
            } else {
                $coursePart = $level;
            }
            if ($grade) {
                $coursePart .= ' ' . strtoupper($grade);
            }

            $destination = $program->destination;
            $year = $program->departure_date ? (int) date('Y', strtotime($program->departure_date)) : $program->year;

            if ($institutionName && $coursePart && $destination && $year) {
                $newName = sprintf('%s - %s - %s - %d', $institutionName, $coursePart, $destination, $year);
                $program->update(['name' => $newName]);
            }
        } catch (\Exception $e) {
            Log::error('Error regenerating program name', [
                'course_id' => $course->id,
                'program_id' => $course->program_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Map education level to standardized format.
     */
    private function mapEducationLevel(string $level): string
    {
        return match ($level) {
            'primaria', 'primario', 'basica' => 'basica',
            'secundaria', 'secundario', 'media' => 'media',
            'preescolar' => 'preescolar',
            'universitaria', 'universitario' => 'universitaria',
            default => $level,
        };
    }
}
