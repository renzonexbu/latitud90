<?php

namespace App\Http\Controllers;

use App\Services\Client\PaymentGateway\KhipuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KhipuWebhookController extends Controller
{
    protected $khipuService;

    public function __construct(KhipuService $khipuService)
    {
        $this->khipuService = $khipuService;
    }

    /**
     * Procesar webhook de Khipu
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        
        Log::info('Khipu webhook received', [
            'data' => $data,
            'headers' => $request->headers->all()
        ]);

        $result = $this->khipuService->processWebhook($data);
        
        if ($result['success']) {
            // Aquí puedes procesar el pago exitoso
            // Por ejemplo, actualizar el estado de la orden
            Log::info('Khipu webhook processed successfully', $result);
            
            // TODO: Implementar lógica para actualizar el estado del pago en la base de datos
            // Por ejemplo:
            // $payment = Payment::where('payment_id', $result['payment_id'])->first();
            // if ($payment) {
            //     $payment->update(['status' => $result['status']]);
            // }
            
            return response()->json(['status' => 'success'], 200);
        } else {
            Log::error('Khipu webhook failed', $result);
            return response()->json(['status' => 'error', 'message' => $result['error']], 400);
        }
    }
}
