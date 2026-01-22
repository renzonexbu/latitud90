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
        
        // Tipos de pago presencial para el formulario
        $paymentTypeOptions = [
            ['value' => 'presential_office_card', 'label' => 'Boleta/Efectivo (BX)', 'report_code' => 'BX'],
            ['value' => 'presential_bank_transfer', 'label' => 'Transferencia Electrónica (TE)', 'report_code' => 'TE'],
            ['value' => 'presential_check', 'label' => 'Cheque (CH)', 'report_code' => 'CH'],
            ['value' => 'presential_deposit', 'label' => 'Depósito (DP)', 'report_code' => 'DP'],
            ['value' => 'presential_aporte', 'label' => 'Aporte (AP)', 'report_code' => 'AP'],
        ];

        return [
            'programs' => $programs,
            'countries' => $countries,
            'regions' => $regions,
            'documentTypes' => $documentTypes,
            'paymentTypeOptions' => $paymentTypeOptions
        ];
    }
}
