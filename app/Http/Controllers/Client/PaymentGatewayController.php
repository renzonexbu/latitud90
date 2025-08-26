<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Services\Client\PaymentGateway\HandleNotificationService;
use App\Services\Client\PaymentGateway\TransbankService;
use App\Services\Client\PaymentGateway\KhipuService;
use App\Services\Client\Data\GetCallbackSpinnerDataService;
use App\Services\Client\Payment\ConfirmTransbankService;
use App\Services\Client\Data\GetKhipuCallbackDataService;
use App\Services\Client\Payment\ConfirmKhipuService;
use App\Services\Client\Payment\ProcessPaymentResultService;
use App\Services\Client\Payment\CheckPaymentStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentGatewayController extends Controller
{
    public function __construct(
        private HandleNotificationService $handleNotificationService,
        private GetCallbackSpinnerDataService $getCallbackSpinnerDataService,
        private ConfirmTransbankService $confirmTransbankService,
        private GetKhipuCallbackDataService $getKhipuCallbackDataService,
        private ConfirmKhipuService $confirmKhipuService,
        private ProcessPaymentResultService $processPaymentResultService,
        private CheckPaymentStatusService $checkPaymentStatusService
    ) {}

    /**
     * Vista con spinner: recibe token_ws y orden, luego el frontend hará POST a confirm.
     */
    public function callbackSpinner(Request $request, $orderDetailId)
    {
        $data = $this->getCallbackSpinnerDataService->execute($request, (int) $orderDetailId);
        
        return inertia('Payment/CallbackSpinner', $data);
    }

    /**
     * Confirmar transacción de Transbank vía POST (commit con token_ws)
     */
    public function confirmTransbank(Request $request)
    {
        $result = $this->confirmTransbankService->execute($request);

        return response()->json($result);
    }

    /**
     * Khipu: vista de spinner que recibe orderDetailId y paymentId
     */
    public function khipuCallbackSpinner(Request $request, $orderDetailId)
    {
        $data = $this->getKhipuCallbackDataService->execute($request, (int) $orderDetailId);

        // Render a la vista en carpeta Payment para evitar conflictos de resolución
        return inertia('Payment/KhipuView', $data);
    }

    /**
     * Khipu: confirmar consultando estado por payment_id
     */
    public function confirmKhipu(Request $request)
    {
        $result = $this->confirmKhipuService->execute($request);

        return response()->json($result);
    }

    /**
     * Manejar notificación de Transbank
     */
    public function handleTransbankNotification(Request $request)
    {
        Log::info('Transbank notification received', $request->all());

        try {
            $notification = $request->all();
            $result = $this->handleNotificationService->handleNotification('transbank', $notification);

            if ($result['success']) {
                return response('OK', 200);
            } else {
                Log::error('Transbank notification processing failed', $result);
                return response($result['error'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Transbank notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return response('Internal server error', 500);
        }
    }

    /**
     * Manejar notificación de Khipu
     */
    public function handleKhipuNotification(Request $request)
    {
        Log::info('Khipu notification received', $request->all());

        try {
            $notification = $request->all();
            $result = $this->handleNotificationService->handleNotification('khipu', $notification);

            if ($result['success']) {
                return response('OK', 200);
            } else {
                Log::error('Khipu notification processing failed', $result);
                return response($result['error'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Khipu notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return response('Internal server error', 500);
        }
    }

    /**
     * Procesar resultado del pago y redirigir según el estado
     */
    public function processPaymentResult(Request $request, $orderDetailId)
    {
        return $this->processPaymentResultService->execute($request, (int) $orderDetailId);
    }

    /**
     * Verificar estado del pago (para polling desde frontend)
     */
    public function checkPaymentStatus($orderDetailId)
    {
        $result = $this->checkPaymentStatusService->execute((int) $orderDetailId);

        return response()->json($result);
    }


}
