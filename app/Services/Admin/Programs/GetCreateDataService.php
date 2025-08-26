<?php

namespace App\Services\Admin\Programs;

use App\Models\Institution;
use App\Models\SalesExecutive;
use App\Traits\AdminLogging;

class GetCreateDataService
{
    use AdminLogging;
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

        // Log the program creation form view
        $this->logView(
            'programs',
            'ProgramCreateForm',
            0, // No specific resource ID for form views
            "Formulario de creación de programa abierto",
            [
                'institutions_count' => $institutions->count(),
                'sales_executives_count' => $salesExecutives->count(),
            ]
        );

        return [
            'institutions' => $institutions,
            'salesExecutives' => $salesExecutives,
        ];
    }
}
