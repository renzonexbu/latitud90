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
}
