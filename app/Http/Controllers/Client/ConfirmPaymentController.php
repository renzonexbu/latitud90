<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ConfirmPaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConfirmPaymentController extends Controller
{
    protected $confirmPaymentService;

    public function __construct(ConfirmPaymentService $confirmPaymentService)
    {
        $this->confirmPaymentService = $confirmPaymentService;
    }

    public function show(Request $request, $programId)
    {
        $rut = $request->query('rut', '');
        $confirmationData = $this->confirmPaymentService->getConfirmationDetails($programId, $request->user()->id ?? null, $rut);
        return Inertia::render('Ecommerce/Confirmation', [
            'confirmationData' => $confirmationData,
            'programId' => $programId,
            'rut' => $rut
        ]);
    }
}
