<?php

namespace App\Services\Admin\Reports;

use App\Models\Program;
use Illuminate\Http\Request;

class GetInstallmentScheduleService
{
    /**
     * Obtener datos para el cronograma de cuotas
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $filters = $request->only(['programId', 'installmentStatus', 'dateFrom']);

        // TODO: Implementar lógica de cuotas
        // Por ahora retornamos datos de ejemplo
        $installments = collect();

        return [
            'installmentSchedule' => $installments,
            'programs' => Program::all(['id', 'name']),
            'filters' => $filters
        ];
    }
}
