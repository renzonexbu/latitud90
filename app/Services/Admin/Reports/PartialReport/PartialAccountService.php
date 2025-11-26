<?php

namespace App\Services\Admin\Reports\PartialReport;

use App\Models\Participant;
use App\Models\Program;
use App\Models\ProgramCourse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PartialAccountService
{
    public function __construct(
        private PartialAccountDataProvider $dataProvider,
        private PartialAccountFilters $filters,
        private PartialAccountTransformer $transformer
    ) {}

    /**
     * Obtiene los datos del estado de cuenta parcial con paginación
     */
    public function getPartialAccounts(array $filters): LengthAwarePaginator
    {
        $query = $this->filters->applyFilters($filters);
        $enrollments = $this->dataProvider->getEnrollments($query, $filters['page'] ?? 1, 15, $filters);
        $transformedData = $this->transformer->transformEnrollments($enrollments->items(), $filters);
        
        return new LengthAwarePaginator(
            $transformedData,
            $enrollments->total(),
            $enrollments->perPage(),
            $enrollments->currentPage(),
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * Obtiene los datos para exportación
     */
    public function getExportData(array $filters, array $selectedFields, string $includeAll = 'current'): Collection
    {
        // Aplicar filtros
        $query = $this->filters->applyFilters($filters);
        
        // Obtener datos según la opción de exportación
        if ($includeAll === 'all') {
            $enrollments = $this->dataProvider->getAllEnrollments($query);
        } else {
            $enrollments = $this->dataProvider->getEnrollments($query, 1);
            $enrollments = collect($enrollments->items());
        }
        
        // Transformar datos para exportación
        return $this->transformer->transformForExport($enrollments, $selectedFields);
    }

    /**
     * Obtiene los datos de filtros disponibles
     */
    public function getFilterData(): array
    {
        $programs = ProgramCourse::select('id', 'code', 'name')
            ->where('active', true)
            ->orderBy('code')
            ->get()
            ->map(function($program) {
                return [
                    'id' => $program->id,
                    'name' => $program->code . ' - ' . $program->name,
                ];
            });

        return [
            'programs' => $programs,
            'participants' => Participant::select('id', 'first_last_name', 'second_last_name', 'first_name', 'second_name')->get(),
        ];
    }

    /**
     * Busca participantes por nombre
     */
    public function searchParticipants(string $search): Collection
    {
        return Participant::select('id', 'first_name', 'second_name', 'first_last_name', 'second_last_name')
            ->where(function($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                      ->orWhere('second_name', 'LIKE', "%{$search}%")
                      ->orWhere('first_last_name', 'LIKE', "%{$search}%")
                      ->orWhere('second_last_name', 'LIKE', "%{$search}%");
            })
            ->limit(20)
            ->get()
            ->map(function($participant) {
                $parts = [];
                if ($participant->first_name) $parts[] = $participant->first_name;
                if ($participant->second_name) $parts[] = $participant->second_name;
                if ($participant->first_last_name) $parts[] = $participant->first_last_name;
                if ($participant->second_last_name) $parts[] = $participant->second_last_name;
                
                return [
                    'id' => $participant->id,
                    'name' => implode(' ', $parts)
                ];
            });
    }
}
