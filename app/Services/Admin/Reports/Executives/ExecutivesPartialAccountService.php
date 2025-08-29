<?php

namespace App\Services\Admin\Reports\Executives;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ExecutivesPartialAccountService
{
    /**
     * Retorna datos para Estado de Cuenta Parcial (apoderados)
     */
    public function getPartialAccounts(array $filters): array
    {
        $page = (int)($filters['page'] ?? 1);

        $items = collect([]);

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, 25)->values(),
            $items->count(),
            25,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'partialAccounts' => $paginator,
            'filters' => [
                'dateFrom' => $filters['dateFrom'] ?? Carbon::now('America/Santiago')->subMonth()->format('Y-m-d'),
                'dateTo' => $filters['dateTo'] ?? Carbon::now('America/Santiago')->format('Y-m-d'),
                'programId' => $filters['programId'] ?? null,
                'guardianQuery' => $filters['guardianQuery'] ?? null,
            ],
            'programs' => \App\Models\Program::select('id', 'code', 'name')->orderBy('code')->get(),
        ];
    }
}


