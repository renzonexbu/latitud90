<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\ConfirmPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $rutFromQuery = $request->query('rut');
        $rut = $rutFromQuery ?? session('current_rut', '');
        if ($rut) {
            $cleanRut = preg_replace('/[.-]/', '', $rut);
            session(['current_rut' => $cleanRut]);
            $rut = $cleanRut;
        }

        // Si no viene rut en la URL pero existe en sesión, redirigir agregando ?rut para persistir en URL
        if (!$rutFromQuery && !empty($rut)) {
            Log::info('ConfirmPaymentController.show adding rut to URL', [
                'program_id' => $programId,
                'rut' => $rut,
            ]);
            $redirect = redirect()->route('payment.confirmation', ['programId' => $programId, 'rut' => $rut]);
            if (session()->has('error')) {
                $redirect->with('error', session('error'));
            }
            return $redirect;
        }
        $confirmationData = $this->confirmPaymentService->getConfirmationDetails($programId, $request->user()->id ?? null, $rut);
        return Inertia::render('Ecommerce/Confirmation', [
            'confirmationData' => $confirmationData,
            'programId' => $programId,
            'rut' => $rut
        ]);
    }
}
