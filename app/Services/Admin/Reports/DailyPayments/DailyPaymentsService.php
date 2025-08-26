<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Traits\AdminLogging;

class DailyPaymentsService
{
    use AdminLogging;
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
        
        // Log the daily payments report view
        $this->logView(
            'reports',
            'DailyPayments',
            0, // No specific resource ID for list views
            "Reporte de pagos diarios consultado - Página {$page}",
            [
                'filters' => $filters,
                'page' => $page,
                'total_records' => $transformed->total(),
                'per_page' => $transformed->perPage(),
                'report_type' => 'daily_payments',
            ]
        );
        
        return $transformed;
    }

    public function getAllDailyPayments(array $filters, array $selectedFields = []): Collection
    {
        $query = $this->dataProvider->buildBaseQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getAllDailyPayments($query);
        $transformed = $this->transformer->transformForExport($data, $selectedFields);

        // Log the daily payments export
        $this->logExport(
            'reports',
            "Exportación de pagos diarios generada",
            [
                'filters' => $filters,
                'selected_fields' => $selectedFields,
                'total_records' => $transformed->count(),
                'report_type' => 'daily_payments_export',
            ]
        );

        return $transformed;
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
