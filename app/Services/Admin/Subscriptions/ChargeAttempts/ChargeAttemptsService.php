<?php

namespace App\Services\Admin\Subscriptions\ChargeAttempts;

use Illuminate\Http\Request;

class ChargeAttemptsService
{
    public function __construct(
        private ChargeAttemptsDataProvider $dataProvider,
        private ChargeAttemptsTransformer $transformer,
        private ChargeAttemptsFilters $filtersService
    ) {}

    /**
     * Obtener todos los intentos de cobro con filtros
     */
    public function getChargeAttempts(Request $request): array
    {
        // Procesar filtros
        $filters = $this->filtersService->process($request);

        // Obtener datos
        $data = $this->dataProvider->getData($filters);

        // Transformar datos
        $transformedData = $this->transformer->transformForView($data);

        // Obtener estadísticas
        $statistics = $this->dataProvider->getStatistics($filters);

        // Obtener opciones de filtros
        $filterOptions = $this->filtersService->getFilterOptions();

        return [
            'chargeAttempts' => $transformedData,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
            'filters' => $filters,
        ];
    }

    /**
     * Obtener intentos de cobro de una suscripción específica
     */
    public function getBySubscription(int $subscriptionId): array
    {
        $data = $this->dataProvider->getBySubscription($subscriptionId);
        $transformedData = $this->transformer->transformForView($data);

        return [
            'chargeAttempts' => $transformedData,
            'statistics' => [
                'total_attempts' => $data->count(),
                'successful_attempts' => $data->where('charge_status', 'success')->count(),
                'failed_attempts' => $data->where('charge_status', 'failed')->count(),
                'pending_attempts' => $data->where('charge_status', 'pending')->count(),
            ],
        ];
    }

    /**
     * Obtener intentos de cobro de un participante específico
     */
    public function getByParticipant(int $participantId, int $programCourseId): array
    {
        $data = $this->dataProvider->getByParticipant($participantId, $programCourseId);
        $transformedData = $this->transformer->transformForView($data);

        return [
            'chargeAttempts' => $transformedData,
            'statistics' => [
                'total_attempts' => $data->count(),
                'successful_attempts' => $data->where('charge_status', 'success')->count(),
                'failed_attempts' => $data->where('charge_status', 'failed')->count(),
            ],
        ];
    }
}
