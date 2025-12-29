<?php

namespace App\Services\Admin\Subscriptions\ChargeAttempts;

use Illuminate\Http\Request;

class ChargeAttemptsFilters
{
    /**
     * Procesar filtros del request
     */
    public function process(Request $request): array
    {
        $filters = [];

        // Filtro por rango de fechas
        if ($request->filled('dateFrom')) {
            $filters['dateFrom'] = $request->input('dateFrom');
        }

        if ($request->filled('dateTo')) {
            $filters['dateTo'] = $request->input('dateTo');
        }

        // Filtro por estado del cargo
        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }

        // Filtro por programa
        if ($request->filled('programId')) {
            $filters['programId'] = $request->input('programId');
        }

        // Filtro por ejecutivo de ventas
        if ($request->filled('salesExecutiveId')) {
            $filters['salesExecutiveId'] = $request->input('salesExecutiveId');
        }

        // Filtro por suscripción específica
        if ($request->filled('subscriptionId')) {
            $filters['subscriptionId'] = $request->input('subscriptionId');
        }

        // Búsqueda de participante
        if ($request->filled('participantSearch')) {
            $filters['participantSearch'] = $request->input('participantSearch');
        }

        // Filtro por tipo de intento
        if ($request->filled('attemptType')) {
            $filters['attemptType'] = $request->input('attemptType');
        }

        return $filters;
    }

    /**
     * Obtener opciones para los filtros
     */
    public function getFilterOptions(): array
    {
        return [
            'statuses' => [
                ['value' => 'success', 'label' => 'Exitoso'],
                ['value' => 'failed', 'label' => 'Fallido'],
                ['value' => 'pending', 'label' => 'Pendiente'],
                ['value' => 'cancelled', 'label' => 'Cancelado'],
            ],
            'attemptTypes' => [
                ['value' => 'automatic', 'label' => 'Automático'],
                ['value' => 'manual', 'label' => 'Manual'],
            ],
        ];
    }
}
