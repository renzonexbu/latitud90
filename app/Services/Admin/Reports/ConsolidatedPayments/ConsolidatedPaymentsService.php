<?php

namespace App\Services\Admin\Reports\ConsolidatedPayments;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ConsolidatedPaymentsService
{
    public function __construct(
        private ConsolidatedPaymentsDataProvider $dataProvider,
        private ConsolidatedPaymentsFilters $filters,
        private ConsolidatedPaymentsTransformer $transformer
    ) {}

    public function getConsolidatedPayments(array $filters, int $page = 1): LengthAwarePaginator
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getConsolidatedPayments($query, $page);
        $transformed = $this->transformer->transformForView($data);
        
        return $transformed;
    }

    public function getAllConsolidatedPayments(array $filters, array $selectedFields = []): Collection
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getAllConsolidatedPayments($query);
        return $this->transformer->transformForExport($data, $selectedFields);
    }

    public function getSummary(array $filters): array
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $summary = $this->dataProvider->getSummary($query);
        
        return $summary;
    }

    public function getPaymentMethods(): Collection
    {
        return $this->dataProvider->getPaymentMethods();
    }

    public function getDefaultDateRange(): array
    {
        return $this->dataProvider->getDefaultDateRange();
    }

    public function getData(array $filters): array
    {
        $page = request()->get('page', 1);
        $consolidatedPayments = $this->getConsolidatedPayments($filters, $page);
        $summary = $this->getSummary($filters);
        $paymentMethods = $this->getPaymentMethods();
        $defaultDateRange = $this->getDefaultDateRange();

        return [
            'consolidatedPayments' => $consolidatedPayments,
            'summary' => $summary,
            'paymentMethods' => $paymentMethods,
            'defaultDateRange' => $defaultDateRange,
        ];
    }
}
