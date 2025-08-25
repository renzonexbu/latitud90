<?php

namespace App\Services\Admin\Payments;

use App\Models\Program;
use App\Models\Country;
use App\Models\Region;
use App\Models\Document;

class GetCreateDataService
{
    /**
     * Obtener datos necesarios para el formulario de creación de pagos
     *
     * @return array
     */
    public function execute(): array
    {
        // Obtener programas activos con participantes
        $programs = Program::with(['course.participants'])->where('active', true)->get();
        
        // Cargar datos para el formulario del comprador
        $countries = Country::where('name', 'Chile')->get();
        $regions = Region::with('comunes')->get();
        $documentTypes = Document::all();
        
        // Métodos de pago presenciales
        $presentialPaymentMethods = [
            ['id' => 'cash', 'name' => 'Efectivo'],
            ['id' => 'debit', 'name' => 'Tarjeta de Débito'],
            ['id' => 'credit', 'name' => 'Tarjeta de Crédito'],
            ['id' => 'transfer', 'name' => 'Transferencia Bancaria'],
        ];

        return [
            'programs' => $programs,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes,
            'presentialPaymentMethods' => $presentialPaymentMethods
        ];
    }
}
