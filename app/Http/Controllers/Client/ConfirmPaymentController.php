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
        $confirmationData = $this->confirmPaymentService->getConfirmationDetails($programId, $request->user()->id ?? null);
        
        return Inertia::render('Ecommerce/Confirmation', [
            'confirmationData' => $confirmationData,
            'programId' => $programId,
            'rut' => $request->query('rut', '')
        ]);
    }
}
