<?php

namespace App\Services\Admin\Programs;

use App\Models\Institution;
use App\Models\SalesExecutive;

class GetCreateDataService
{
    /**
     * Obtener datos necesarios para el formulario de creación de programas
     *
     * @return array
     */
    public function execute(): array
    {
        $institutions = Institution::orderBy('name')->get();
        $salesExecutives = SalesExecutive::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return [
            'institutions' => $institutions,
            'salesExecutives' => $salesExecutives,
        ];
    }
}
