<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function index(Request $request)
    {
        // Por ahora retornamos datos vacíos
        // Aquí se implementará la lógica de consulta cuando esté listo
        return Inertia::render('Admin/Programs/Index', [
            'programs' => [],
            'filters' => $request->only(['search', 'service_type', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Programs/Create');
    }

    public function store(Request $request)
    {
        // Por ahora solo redirigimos al index
        // Aquí se implementará la lógica de guardado cuando esté listo
        return redirect()->route('admin.programs.index')
            ->with('success', 'Programa creado exitosamente.');
    }

    public function show(Program $program)
    {
        $program->load(['passengers']);

        return Inertia::render('Admin/Programs/Show', [
            'program' => $program
        ]);
    }

    public function edit(Program $program)
    {
        return Inertia::render('Admin/Programs/Edit', [
            'program' => $program
        ]);
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'service_type' => 'required|in:tours,excursiones,intercambio,cruceros',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'duration_days' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'includes' => 'nullable|string',
            'excludes' => 'nullable|string',
            'requirements' => 'nullable|string',
            'itinerary' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'active' => 'boolean'
        ]);

        // Manejar subida de imagen
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($program->image_url) {
                Storage::disk('public')->delete($program->image_url);
            }

            $imagePath = $request->file('image')->store('programs', 'public');
            $validated['image_url'] = $imagePath;
        }

        $program->update($validated);

        return redirect()->route('admin.programs.index')
            ->with('success', 'Programa actualizado exitosamente.');
    }

    public function destroy(Program $program)
    {
        // Eliminar imagen si existe
        if ($program->image_url) {
            Storage::disk('public')->delete($program->image_url);
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
        $passengers = $program->passengers()
            ->with(['payments'])
            ->where('status', '!=', 'cancelled')
            ->paginate(10);

        return Inertia::render('Admin/Programs/Passengers', [
            'program' => $program,
            'passengers' => $passengers,
        ]);
    }

    public function bulkAction(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'program_ids' => 'required|array',
            'program_ids.*' => 'exists:programs,id'
        ]);

        $programs = Program::whereIn('id', $validated['program_ids']);

        switch ($validated['action']) {
            case 'activate':
                $programs->update(['active' => true]);
                $message = 'Programas activados exitosamente.';
                break;
            case 'deactivate':
                $programs->update(['active' => false]);
                $message = 'Programas desactivados exitosamente.';
                break;
            case 'delete':
                // Eliminar imágenes asociadas
                $programsToDelete = $programs->get();
                foreach ($programsToDelete as $program) {
                    if ($program->image_url) {
                        Storage::disk('public')->delete($program->image_url);
                    }
                }
                $programs->delete();
                $message = 'Programas eliminados exitosamente.';
                break;
        }

        return back()->with('success', $message);
    }
}
