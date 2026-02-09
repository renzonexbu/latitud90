<?php

namespace App\Http\Controllers\Admin\Courses;

use App\Http\Controllers\Controller;
use App\Services\Admin\Courses\PaymentOptionsProgramService;
use App\Services\Commands\UpdatePaymentOptionsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentOptionsProgramController extends Controller
{
    public function __construct(
        private PaymentOptionsProgramService $service
    ) {}

    /**
     * Vista principal con filtros y tabla de programas por medio de pago
     */
    public function index(Request $request)
    {
        // Sincronizar opciones de pago y subscription_max_months antes de mostrar el reporte
        (new UpdatePaymentOptionsService())->updatePaymentOptions();

        $filters = $request->only(['paymentOptionId', 'salesExecutiveId', 'search']);

        $data = $this->service->getProgramsByPaymentOption($filters);

        return Inertia::render('Admin/Courses/PaymentOptionsPrograms', [
            'programs' => $data['programs'],
            'paymentOptions' => $data['paymentOptions'],
            'salesExecutives' => $data['salesExecutives'],
            'filters' => $filters,
            'summary' => $data['summary'],
        ]);
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        // Sincronizar opciones de pago antes de exportar
        (new UpdatePaymentOptionsService())->updatePaymentOptions();

        $filters = $request->only(['paymentOptionId', 'salesExecutiveId', 'search']);

        return $this->service->exportToExcel($filters);
    }
}
