<?php

namespace App\Services\Admin\Reports\PartialReport;

use App\Models\Participant;
use App\Models\Program;
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
        // Aplicar filtros
        $query = $this->filters->applyFilters($filters);
        
        // Obtener datos paginados
        $enrollments = $this->dataProvider->getEnrollments($query, $filters['page'] ?? 1);
        
        // Transformar datos
        $transformedData = $this->transformer->transformEnrollments($enrollments->items());
        
        // Crear paginador personalizado
        return new LengthAwarePaginator(
            $transformedData,
            $enrollments->total(),
            $enrollments->perPage(),
            $enrollments->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
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
        return [
            'programs' => Program::select('id', 'name')->get(),
            'participants' => Participant::select('id', 'first_name', 'last_name')->get(),
        ];
    }
}
