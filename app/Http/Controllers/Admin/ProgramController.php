<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProgramRequest;
use App\Models\Institution;
use App\Models\Program;
use App\Models\PaymentMode;
use App\Services\Admin\Programs\CreateProgramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class ProgramController extends Controller
{
    public function __construct(
        private CreateProgramService $createProgramService
    ) {}

    public function index(Request $request)
    {
        $programs = Program::with(['paymentMode', 'course.institution', 'participants'])
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
        // Cargar las imágenes para cada programa
        $programs->getCollection()->transform(function ($program) {
            $program->images = $program->images;
            return $program;
        });

        return Inertia::render('Admin/Programs/Index', [
            'programs' => $programs,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        $institutions = Institution::orderBy('name')->get();
        
        return Inertia::render('Admin/Programs/Create', [
            'institutions' => $institutions,
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
        $program->load(['course', 'participants']);

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
        return Inertia::render('Admin/Programs/Edit', [
            'program' => $program
        ]);
    }

    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'departure_date' => 'required|date|after:today',
            'trip_description' => 'required|string|max:2000',
            'images_folder' => 'nullable|string|max:255',
            'pillars' => 'nullable|string|max:500',
            'itinerary_description' => 'nullable|string|max:1000',
            'itinerary_file' => 'nullable|file|mimes:pdf|max:10240',
            'travel_assistance_coverage' => 'nullable|file|mimes:pdf|max:10240',
            'equipment_list' => 'nullable|file|mimes:pdf|max:10240',
            'trip_price' => 'required|numeric|min:0',
            'final_payment_date' => 'required|date|after:today',
            'seller_name' => 'required|string|max:255',
            'payment_mode_id' => 'required|exists:payment_modes,id',
            'active' => 'boolean'
        ]);

        // Manejar subida de archivos
        if ($request->hasFile('itinerary_file')) {
            if ($program->itinerary_file) {
                Storage::disk('public')->delete($program->itinerary_file);
            }
            $validated['itinerary_file'] = $request->file('itinerary_file')->store('programs/files', 'public');
        }

        if ($request->hasFile('travel_assistance_coverage')) {
            if ($program->travel_assistance_coverage) {
                Storage::disk('public')->delete($program->travel_assistance_coverage);
            }
            $validated['travel_assistance_coverage'] = $request->file('travel_assistance_coverage')->store('programs/files', 'public');
        }

        if ($request->hasFile('equipment_list')) {
            if ($program->equipment_list) {
                Storage::disk('public')->delete($program->equipment_list);
            }
            $validated['equipment_list'] = $request->file('equipment_list')->store('programs/files', 'public');
        }

        $program->update($validated);

        return redirect()->route('admin.programs.index')
            ->with('success', 'Programa actualizado exitosamente.');
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
                // Eliminar archivos asociados
                $programsToDelete = $programs->get();
                foreach ($programsToDelete as $program) {
                    if ($program->itinerary_file) {
                        Storage::disk('public')->delete($program->itinerary_file);
                    }
                    if ($program->travel_assistance_coverage) {
                        Storage::disk('public')->delete($program->travel_assistance_coverage);
                    }
                    if ($program->equipment_list) {
                        Storage::disk('public')->delete($program->equipment_list);
                    }
                }
                $programs->delete();
                $message = 'Programas eliminados exitosamente.';
                break;
        }

        return back()->with('success', $message);
    }
}
