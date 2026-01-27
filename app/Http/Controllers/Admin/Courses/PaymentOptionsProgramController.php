<?php

namespace App\Http\Controllers\Admin\Courses;

use App\Http\Controllers\Controller;
use App\Services\Admin\Courses\PaymentOptionsProgramService;
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
        $filters = $request->only(['paymentOptionId', 'active', 'search']);

        $data = $this->service->getProgramsByPaymentOption($filters);

        return Inertia::render('Admin/Courses/PaymentOptionsPrograms', [
            'programs' => $data['programs'],
            'paymentOptions' => $data['paymentOptions'],
            'filters' => $filters,
            'summary' => $data['summary'],
        ]);
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        $filters = $request->only(['paymentOptionId', 'active', 'search']);

        return $this->service->exportToExcel($filters);
    }
}
