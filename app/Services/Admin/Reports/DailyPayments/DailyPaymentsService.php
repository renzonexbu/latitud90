<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DailyPaymentsService
{
    public function __construct(
        private DailyPaymentsDataProvider $dataProvider,
        private DailyPaymentsFilters $filters,
        private DailyPaymentsTransformer $transformer
    ) {}

    public function getDailyPayments(array $filters, int $page = 1): LengthAwarePaginator
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getDailyPayments($query, $page);
        $transformed = $this->transformer->transformForView($data);
        
        return $transformed;
    }

    public function getAllDailyPayments(array $filters, array $selectedFields = []): Collection
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getAllDailyPayments($query);
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
        return $this->dataProvider->getPrograms();
    }

    public function getSalesExecutives(): Collection
    {
        return $this->dataProvider->getSalesExecutives();
    }

    public function getFinancingTypes(): array
    {
        return $this->dataProvider->getFinancingTypes();
    }

    public function getPaymentMethods(): Collection
    {
        return $this->dataProvider->getPaymentMethods();
    }

    public function getDefaultDateRange(): array
    {
        return $this->dataProvider->getDefaultDateRange();
    }
}
