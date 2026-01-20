<?php

namespace App\Http\Controllers\Admin\Programs;   

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Program;
use App\Services\Admin\Programs\CreateProgramService;
use App\Services\Admin\Programs\UpdateProgramService;
use App\Services\Admin\Programs\BulkProgramsActionService;
use App\Services\Admin\Programs\GetProgramsService;
use App\Services\Admin\Programs\GetCreateDataService;
use App\Services\Admin\Programs\GetShowDataService;
use App\Services\Admin\Programs\GetFilesService;
use App\Services\Admin\Programs\GetEditDataService;
use App\Services\Admin\Programs\DeleteProgramService;
use App\Services\Admin\Programs\ToggleStatusService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Admin\Programs\BulkProgramsActionRequest;

class ProgramController extends Controller
{
    public function __construct(
        private CreateProgramService $createProgramService,
        private UpdateProgramService $updateProgramService,
        private BulkProgramsActionService $bulkProgramsActionService,
        private GetProgramsService $getProgramsService,
        private GetCreateDataService $getCreateDataService,
        private GetShowDataService $getShowDataService,
        private GetFilesService $getFilesService,
        private GetEditDataService $getEditDataService,
        private DeleteProgramService $deleteProgramService,
        private ToggleStatusService $toggleStatusService
    ) {}

    public function index(Request $request)
    {
        $data = $this->getProgramsService->execute($request);

        return Inertia::render('Admin/Programs/Index', $data);
    }

    public function create()
    {
        $data = $this->getCreateDataService->execute();

        return Inertia::render('Admin/Programs/Create', $data);
    }

    public function store(CreateProgramRequest $request)
    {
        try {
            $program = $this->createProgramService->execute($request->validated());

            return redirect()->route('admin.programs.index')
                ->with('success', 'Plantilla de programa creada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear la plantilla: ' . $e->getMessage()]);
        }
    }

    public function show(Program $program)
    {
        $data = $this->getShowDataService->execute($program);

        return Inertia::render('Admin/Programs/Show', $data);
    }

    /**
     * Show program files and images
     */
    public function files(Program $program)
    {
        $data = $this->getFilesService->execute($program);

        return response()->json($data);
    }

    public function edit(Program $program)
    {
        $data = $this->getEditDataService->execute($program);

        return Inertia::render('Admin/Programs/Edit', $data);
    }

    public function update(UpdateProgramRequest $request, Program $program)
    {
        try {
            $validated = $request->validated();

            Log::info('ProgramController@update: Actualizando plantilla de programa', [
                'program_id' => $program->id,
                'keys' => array_keys($validated),
            ]);

            $program = $this->updateProgramService->execute($validated, $program);

            return redirect()->route('admin.programs.index')
                ->with('success', 'Plantilla de programa actualizada exitosamente.');
        } catch (\Exception $e) {
            Log::error('ProgramController@update: Error al actualizar plantilla', [
                'program_id' => $program->id,
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Error al actualizar la plantilla: ' . $e->getMessage()]);
        }
    }

    /**
     * Verificar si la plantilla puede ser eliminada
     */
    public function canDelete(Program $program)
    {
        $result = $this->deleteProgramService->canDelete($program);

        return response()->json($result);
    }

    public function destroy(Program $program)
    {
        try {
            $this->deleteProgramService->execute($program);

            return redirect()->route('admin.programs.index')
                ->with('success', 'Plantilla de programa eliminada exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function toggleStatus(Program $program)
    {
        $this->toggleStatusService->execute($program);

        return back()->with('success', 'Estado de la plantilla actualizado exitosamente.');
    }

    public function bulkAction(BulkProgramsActionRequest $request)
    {
        $validated = $request->validated();
        $message = $this->bulkProgramsActionService->execute($validated['action'], $validated['program_ids']);
        return back()->with('success', $message);
    }
}
