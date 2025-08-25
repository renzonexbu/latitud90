<?php

namespace App\Http\Controllers\Admin;

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
use App\Services\Admin\Programs\GetPassengersService;
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
        private ToggleStatusService $toggleStatusService,
        private GetPassengersService $getPassengersService
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
                ->with('success', 'Programa creado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al crear el programa: ' . $e->getMessage()]);
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
            Log::info('ProgramController@update: Request method y headers', [
                'program_id' => $program->id,
                'method' => $request->method(),
                'is_method_put' => $request->isMethod('PUT'),
                'is_method_post' => $request->isMethod('POST'),
                'content_type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
            ]);

            Log::info('ProgramController@update: Request all()', [
                'program_id' => $program->id,
                'all' => $request->all(),
                'input' => $request->input(),
                'files' => $request->allFiles(),
            ]);

            $validated = $request->validated();
            Log::info('ProgramController@update: Datos validados recibidos', [
                'program_id' => $program->id,
                'keys' => array_keys($validated),
                'payment_option' => $validated['payment_option'] ?? null,
                'payment_options' => $validated['payment_options'] ?? null,
                'full_payment_method' => $validated['full_payment_method'] ?? null,
                'installments_payment_method' => $validated['installments_payment_method'] ?? null,
                'max_installments' => $validated['max_installments'] ?? null,
            ]);

            $program = $this->updateProgramService->execute($validated, $program);

            return redirect()->route('admin.programs.index')
                ->with('success', 'Programa actualizado exitosamente.');
        } catch (\Exception $e) {
            Log::error('ProgramController@update: Error al actualizar programa', [
                'program_id' => $program->id,
                'message' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Error al actualizar el programa: ' . $e->getMessage()]);
        }
    }

    public function destroy(Program $program)
    {
        try {
            $this->deleteProgramService->execute($program);

            return redirect()->route('admin.programs.index')
                ->with('success', 'Programa eliminado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar el programa: ' . $e->getMessage()]);
        }
    }

    public function toggleStatus(Program $program)
    {
        $this->toggleStatusService->execute($program);

        return back()->with('success', 'Estado del programa actualizado exitosamente.');
    }

    public function passengers(Program $program)
    {
        $data = $this->getPassengersService->execute($program);

        return Inertia::render('Admin/Programs/Passengers', $data);
    }

    public function bulkAction(BulkProgramsActionRequest $request)
    {
        $validated = $request->validated();
        $message = $this->bulkProgramsActionService->execute($validated['action'], $validated['program_ids']);
        return back()->with('success', $message);
    }
}
