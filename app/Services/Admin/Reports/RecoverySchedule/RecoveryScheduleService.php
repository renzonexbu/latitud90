<?php

namespace App\Services\Admin\Reports\RecoverySchedule;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class RecoveryScheduleService
{
    public function __construct(
        private RecoveryScheduleDataProvider $dataProvider,
        private RecoveryScheduleFilters $filters,
        private RecoveryScheduleTransformer $transformer
    ) {}

    public function getPaymentSchedules(array $filters, int $page = 1): LengthAwarePaginator
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        
        // Debug log para verificar la consulta
        Log::info('RecoverySchedule Query', [
            'filters' => $filters,
            'page' => $page,
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
        
        $data = $this->dataProvider->getPaymentSchedules($query, $page);
        $transformed = $this->transformer->transformForView($data);
        
        // Debug log para verificar los datos transformados
        Log::info('RecoverySchedule Data', [
            'total' => $transformed->total(),
            'per_page' => $transformed->perPage(),
            'current_page' => $transformed->currentPage(),
            'first_item' => $transformed->first()
        ]);
        
        return $transformed;
    }

    public function getAllPaymentSchedules(array $filters): Collection
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getAllPaymentSchedules($query);
        return $this->transformer->transformForExport($data);
    }

    public function getSummary(array $filters): array
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $summary = $this->dataProvider->getSummary($query);
        
        // Debug log para verificar el resumen
        Log::info('RecoverySchedule Summary', [
            'filters' => $filters,
            'summary' => $summary,
            'query_sql' => $query->toSql(),
            'query_bindings' => $query->getBindings()
        ]);
        
        return $summary;
    }

    public function getPrograms(): Collection
    {
        $programs = $this->dataProvider->getPrograms();
        
        // Debug log para verificar los programas
        Log::info('RecoverySchedule Programs', [
            'count' => $programs->count(),
            'first_program' => $programs->first()
        ]);
        
        return $programs;
    }
}
