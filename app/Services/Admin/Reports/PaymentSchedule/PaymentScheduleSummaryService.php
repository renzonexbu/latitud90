<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use App\Traits\AdminLogging;

class PaymentScheduleSummaryService
{
    use AdminLogging;

    public function __construct(
        private PaymentScheduleSummaryDataProvider $dataProvider,
        private PaymentScheduleSummaryFilters $filters,
        private PaymentScheduleSummaryTransformer $transformer
    ) {}

    public function getExecutiveSummary(array $filters): Collection
    {
        $query = $this->dataProvider->buildExecutiveSummaryQuery();
        $query = $this->filters->applyFilters($query, $filters);
        $data = $this->dataProvider->getExecutiveSummaryData($query);
        
        return $this->transformer->transformExecutiveSummary($data);
    }

    public function getPrograms(): Collection
    {
        return $this->dataProvider->getPrograms();
    }

    public function getSalesExecutives(): Collection
    {
        return $this->dataProvider->getSalesExecutives();
    }

    public function getSummaryData(array $filters): array
    {
        $executiveSummary = $this->getExecutiveSummary($filters);
        $programs = $this->getPrograms();
        $salesExecutives = $this->getSalesExecutives();
        
        // Obtener datos de cronograma de pagos
        $paymentSchedules = $this->dataProvider->getPaymentSchedules($filters);
        
        // Obtener resumen general
        $summary = $this->dataProvider->getGeneralSummary($filters);

        return [
            'executiveSummary' => $executiveSummary,
            'programs' => $programs,
            'salesExecutives' => $salesExecutives,
            'paymentSchedules' => $paymentSchedules,
            'summary' => $summary,
        ];
    }
}
