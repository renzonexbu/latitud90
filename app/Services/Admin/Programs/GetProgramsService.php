<?php

namespace App\Services\Admin\Programs;

use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;

class GetProgramsService
{
    use AdminLogging;
    /**
     * Obtener programas con filtros y métricas de pagos
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        // Obtener programas paginados
        $programs = $this->getPaginatedPrograms($request);
        
        // Obtener todos los programas para filtros
        $allPrograms = $this->getAllPrograms();

        // Log the programs list view
        $this->logView(
            'programs',
            'ProgramList',
            0, // No specific resource ID for list views
            "Lista de programas consultada - Total: {$programs->total()} registros",
            [
                'total_programs' => $programs->total(),
                'current_page' => $programs->currentPage(),
                'per_page' => $programs->perPage(),
                'filters_applied' => $request->only(['search', 'status', 'active']),
            ]
        );

        return [
            'programs' => $programs,
            'allPrograms' => $allPrograms,
            'filters' => $request->only(['search', 'status', 'active'])
        ];
    }

    /**
     * Obtener programas (plantillas) paginados con filtros
     *
     * @param Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function getPaginatedPrograms(Request $request)
    {
        $programs = Program::when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%");
            })
            ->when($request->active !== null, function ($query) use ($request) {
                $query->where('active', $request->active);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Cargar imágenes para cada plantilla
        $programs->getCollection()->transform(function ($program) {
            $program->images = $program->images;
            return $program;
        });

        return $programs;
    }

    /**
     * Obtener todas las plantillas de programas para filtros
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getAllPrograms()
    {
        $allPrograms = Program::orderBy('created_at', 'desc')
            ->get();

        // Cargar imágenes para cada plantilla
        $allPrograms->transform(function ($program) {
            $program->images = $program->images;
            return $program;
        });

        return $allPrograms;
    }

}
