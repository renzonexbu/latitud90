<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CoursesController extends Controller
{
    public function index(Request $request)
    {
        // Por ahora retornamos datos vacíos
        // Aquí se implementará la lógica de consulta cuando esté listo
        return Inertia::render('Admin/Courses/Index', [
            'courses' => [],
            'filters' => $request->only(['search', 'category', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Courses/Create');
    }

    public function store(Request $request)
    {
        // Por ahora solo redirigimos al index
        // Aquí se implementará la lógica de guardado cuando esté listo
        return redirect()->route('admin.courses.index')
            ->with('success', 'Curso creado exitosamente.');
    }

    public function show($course)
    {
        // Por ahora retornamos vista básica
        // Aquí se implementará la lógica de consulta cuando esté listo
        return Inertia::render('Admin/Courses/Show', [
            'course' => []
        ]);
    }

    public function edit($course)
    {
        // Por ahora retornamos vista básica
        // Aquí se implementará la lógica de consulta cuando esté listo
        return Inertia::render('Admin/Courses/Edit', [
            'course' => []
        ]);
    }

    public function update(Request $request, $course)
    {
        // Por ahora solo redirigimos al index
        // Aquí se implementará la lógica de actualización cuando esté listo
        return redirect()->route('admin.courses.index')
            ->with('success', 'Curso actualizado exitosamente.');
    }

    public function destroy($course)
    {
        // Por ahora solo redirigimos al index
        // Aquí se implementará la lógica de eliminación cuando esté listo
        return redirect()->route('admin.courses.index')
            ->with('success', 'Curso eliminado exitosamente.');
    }

    public function toggleStatus($course)
    {
        // Por ahora solo redirigimos al index
        // Aquí se implementará la lógica de toggle de status cuando esté listo
        return redirect()->route('admin.courses.index')
            ->with('success', 'Estado del curso actualizado exitosamente.');
    }
}
