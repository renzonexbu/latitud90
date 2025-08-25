<?php

namespace App\Services\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GetCallbackSpinnerDataService
{
    /**
     * Obtener datos para la vista de callback spinner
     *
     * @param Request $request
     * @param int $orderDetailId
     * @return array
     */
    public function execute(Request $request, int $orderDetailId): array
    {
        // Pasar RUT desde sesión para preservarlo en redirecciones
        $rut = session('current_rut');
        
        Log::info('GetCallbackSpinnerDataService: callbackSpinner', [
            'order_detail_id' => $orderDetailId,
            'token_ws' => $request->query('token_ws'),
            'rut_in_session' => $rut,
            'full_url' => $request->fullUrl()
        ]);

        return [
            'orderDetailId' => $orderDetailId,
            'token' => $request->query('token_ws') ?? null,
            'rut' => $rut,
        ];
    }
}
