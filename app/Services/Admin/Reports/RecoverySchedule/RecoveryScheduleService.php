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
        
        $data = $this->dataProvider->getPaymentSchedules($query, $page);
        $transformed = $this->transformer->transformForView($data);
        
        return $transformed;
    }

    public function getAllPaymentSchedules(array $filters, array $selectedFields = []): Collection
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getAllPaymentSchedules($query);
        return $this->transformer->transformForExport($data, $selectedFields);
    }

    public function getSummary(array $filters): array
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $summary = $this->dataProvider->getSummary($query);
        return $summary;
    }

    public function getPrograms(): Collection
    {
        $programs = $this->dataProvider->getPrograms();
        return $programs;
    }
}
