<?php

namespace App\Services\Admin\Reports\PaymentSchedule;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Traits\AdminLogging;

class PaymentScheduleDetailService
{
    use AdminLogging;

    public function __construct(
        private PaymentScheduleDetailDataProvider $dataProvider,
        private PaymentScheduleDetailFilters $filters,
        private PaymentScheduleDetailTransformer $transformer
    ) {}

    public function getScheduleDetails(array $filters): Collection
    {
        $query = $this->dataProvider->buildDetailQuery();
        $query = $this->filters->applyFilters($query, $filters);
        
        // Log para debug
        Log::info('Payment Schedule Detail Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $filters
        ]);
        
        $data = $this->dataProvider->getScheduleDetails($query);
        
        // Log de datos obtenidos
        Log::info('Payment Schedule Detail Raw Data:', [
            'count' => $data->count(),
            'sample' => $data->take(3)->toArray()
        ]);
        
        return $this->transformer->transformForView($data);
    }

    /**
     * Obtener solo participantes liberados para mostrar siempre
     */
    public function getLiberatedParticipants(array $filters): Collection
    {
        $liberatedParticipants = $this->dataProvider->getLiberatedParticipantsForMonth($filters);
        return $this->transformer->transformForView($liberatedParticipants);
    }
}
