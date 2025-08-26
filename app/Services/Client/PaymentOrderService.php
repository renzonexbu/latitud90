<?php

namespace App\Services\Client;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Participant;
use App\Models\Program;
use App\Models\Installment;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;

class PaymentOrderService
{
    use SystemLogging;
    /**
     * Crear una nueva orden de pago para una cuota específica
     */
    public function createPaymentOrder(Installment $installment, array $paymentData, array $formData): array
    {
        try {
            DB::beginTransaction();

            $participant = $installment->installmentPlan->participant;
            $program = $installment->installmentPlan->program;

            // Crear nueva orden para este pago específico
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'total_amount' => $installment->amount,
                'discount' => 0,
                'final_amount' => $installment->amount,
                'total_installments' => 1, // Siempre 1 para pagos de cuotas
                'payment_type' => 'monthly', // Pago de cuota mensual
                'status' => 'pending',
                'order_number' => $this->generateOrderNumber(),
                'session_id' => $paymentData['session_id'] ?? null,
                'notes' => "Pago de cuota {$installment->installment_number} del plan {$installment->installmentPlan->id}"
            ]);

            // Crear nuevo order_detail para este pago específico
            $orderDetail = $this->createOrderDetail($order, $paymentData, $formData, $installment);

            $this->logInfo('Payment order created successfully', [
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'installment_id' => $installment->id,
                'installment_number' => $installment->installment_number,
                'amount' => $installment->amount,
                'session_id' => $order->session_id
            ]);

            DB::commit();

            return [
                'success' => true,
                'order' => $order,
                'order_detail' => $orderDetail,
                'installment' => $installment
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Error creating payment order', [
                'error' => $e->getMessage(),
                'installment_id' => $installment->id
            ], $e);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear una nueva orden de pago para pago total (no cuotas)
     */
    public function createTotalPaymentOrder(int $programId, string $rut, array $paymentData, array $formData): array
    {
        try {
            DB::beginTransaction();

            // Buscar el participante por RUT
            $participant = Participant::where('document_number', $rut)->first();
            if (!$participant) {
                throw new \Exception('Participante no encontrado');
            }

            // Buscar el programa
            $program = Program::with(['course.participants'])->findOrFail($programId);

            // Calcular montos por participante
            [$participantTotalAmount, $paidAmount, $participantBalance] = $this->computeParticipantAmounts($program, $participant);
            $finalAmount = $participantBalance;

            // Crear nueva orden para pago total
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'total_amount' => $participantTotalAmount,
                'discount' => 0,
                'final_amount' => $finalAmount,
                'total_installments' => 1,
                'payment_type' => 'total',
                'status' => 'pending',
                'order_number' => $this->generateOrderNumber(),
                'session_id' => $paymentData['session_id'] ?? null,
                'notes' => 'Orden creada para pago total del programa'
            ]);

            // Crear nuevo order_detail para pago total
            $orderDetail = $this->createOrderDetail($order, $paymentData, $formData);

            $this->logInfo('Total payment order created successfully', [
                'order_id' => $order->id,
                'order_detail_id' => $orderDetail->id,
                'amount' => $finalAmount,
                'session_id' => $order->session_id
            ]);

            DB::commit();

            return [
                'success' => true,
                'order' => $order,
                'order_detail' => $orderDetail
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Error creating total payment order', [
                'error' => $e->getMessage(),
                'program_id' => $programId,
                'rut' => $rut
            ], $e);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear order_detail para una orden
     */
    private function createOrderDetail(Order $order, array $paymentData, array $formData, ?Installment $installment = null): OrderDetail
    {
        // Mapear datos del formulario
        $mappedFormData = $this->mapFormData($formData);
        
        // Resolver IDs de ubicación
        $countryId = $this->resolveLocationId($mappedFormData['country'], 'country');
        $regionId = $this->resolveLocationId($mappedFormData['region'], 'region');
        $cityId = $this->resolveLocationId($mappedFormData['city'], 'city');
        $documentTypeId = $this->resolveDocumentTypeId($mappedFormData['document_type']);

        // Determinar payment_option_id
        $paymentOptionId = $this->resolvePaymentOptionId($order->program_id, $paymentData);

        // Para pagos de cuotas, NO establecer payment_gateway_id inicialmente
        // Solo se establecerá cuando se procese el pago
        $paymentGatewayId = null;

        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_option_id' => $paymentOptionId,
            
            // Datos del comprador (siempre actuales)
            'name' => $mappedFormData['name'],
            'email' => $mappedFormData['email'],
            'country' => $countryId,
            'region' => $regionId,
            'city' => $cityId,
            'code_phone' => $mappedFormData['code_phone'],
            'phone' => $mappedFormData['phone'],
            'document_type' => $documentTypeId,
            'document_number' => $mappedFormData['document_number'],
            
            // Dirección de facturación
            'billing_address' => $formData['billing_address'] ?? null,
            'billing_city' => $mappedFormData['city_name'] ?? $mappedFormData['city'],
            'billing_country' => $mappedFormData['country_name'] ?? $mappedFormData['country'],
            'billing_postal_code' => $formData['billing_postal_code'] ?? null,
            
            // Acuerdos
            'terms_accepted' => $mappedFormData['terms_accepted'],
            'marketing_accepted' => $mappedFormData['marketing_accepted'],
            'terms_accepted_confirmation' => $paymentData['termsAccepted'] ?? false,
            
            // Información del pago
            'installment_number' => $installment ? $installment->installment_number : 1,
            'base_amount' => $order->final_amount,
            'discount_amount' => 0,
            'amount' => $order->final_amount,
            'due_date' => $installment ? $installment->due_date : now(),
            'is_paid' => false,
            'status' => 'pending',
            
            // NO establecer payment_gateway_id inicialmente
            'payment_gateway_id' => $paymentGatewayId,
        ]);
    }

    /**
     * Mapear datos del formulario
     */
    private function mapFormData(array $formData): array
    {
        if (isset($formData['buyerData']) && is_array($formData['buyerData'])) {
            $formData = array_merge($formData, $formData['buyerData']);
        }

        return [
            'name' => $formData['name'] ?? ($formData['fullName'] ?? null),
            'email' => $formData['email'] ?? null,
            'country' => $formData['countryId'] ?? ($formData['country'] ?? null),
            'region' => $formData['regionId'] ?? ($formData['region'] ?? null),
            'city' => $formData['cityId'] ?? ($formData['city'] ?? null),
            'code_phone' => $formData['code_phone'] ?? null,
            'phone' => $formData['phone'] ?? null,
            'document_type' => $formData['documentType'] ?? null,
            'document_number' => $formData['documentNumber'] ?? null,
            'terms_accepted' => $formData['termsAccepted'] ?? false,
            'marketing_accepted' => $formData['marketingAccepted'] ?? false,
            'country_name' => $formData['countryName'] ?? null,
            'region_name' => $formData['regionName'] ?? null,
            'city_name' => $formData['cityName'] ?? null,
        ];
    }

    /**
     * Resolver IDs de ubicación
     */
    private function resolveLocationId($value, string $type): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        
        $string = trim((string) $value);
        
        switch ($type) {
            case 'country':
                if (strlen($string) <= 3) {
                    $id = \App\Models\Country::where('code', $string)->value('id');
                    if ($id) { return (int) $id; }
                }
                $id = \App\Models\Country::where('name', $string)->value('id');
                if ($id) { return (int) $id; }
                $id = \App\Models\Country::where('name', 'like', $string)->value('id');
                return $id ? (int) $id : null;
                
            case 'region':
                $id = \App\Models\Region::where('name', $string)->value('id');
                if ($id) { return (int) $id; }
                $id = \App\Models\Region::where('name', 'like', $string)->value('id');
                return $id ? (int) $id : null;
                
            case 'city':
                $id = \App\Models\Comune::where('name', $string)->value('id');
                if ($id) { return (int) $id; }
                $id = \App\Models\Comune::where('name', 'like', $string)->value('id');
                return $id ? (int) $id : null;
                
            default:
                return null;
        }
    }

    /**
     * Resolver ID del tipo de documento
     */
    private function resolveDocumentTypeId($value): ?int
    {
        if (empty($value)) { return null; }
        if (is_numeric($value)) { return (int) $value; }
        
        $string = trim((string) $value);
        $id = \App\Models\Document::where('name', $string)->value('id');
        if ($id) { return (int) $id; }
        $id = \App\Models\Document::where('name', 'like', $string)->value('id');
        return $id ? (int) $id : null;
    }

    /**
     * Resolver payment_option_id
     */
    private function resolvePaymentOptionId(int $programId, array $paymentData): ?int
    {
        $mode = ($paymentData['paymentType'] ?? 'total') === 'monthly' ? 'lat90' : 'full';
        $method = $paymentData['paymentMethod'] ?? 'debit';
        $code = null;
        
        // Log para debugging
        $this->logInfo('PaymentOrderService: Resolving payment option', [
            'program_id' => $programId,
            'payment_type' => $paymentData['paymentType'] ?? 'unknown',
            'payment_method' => $method,
            'mode' => $mode
        ]);
        
        if ($mode === 'full') {
            switch ($method) {
                case 'khipu': $code = 'full_transfer_khipu'; break;
                case 'debit_credit_0': $code = 'full_debit_credit_0'; break;
                case 'debit_credit_3': $code = 'full_debit_credit_3'; break;
                case 'debit_credit_6': $code = 'full_debit_credit_6'; break;
                case 'debit_credit_9': $code = 'full_debit_credit_9'; break;
                case 'debit_credit_12': $code = 'full_debit_credit_12'; break;
                default:
                    // Fallback para códigos legacy
                    if ($method === 'debit') {
                        $code = 'full_debit_credit_0';
                    } elseif (strpos($method, 'credit') === 0) {
                        $suffix = trim(str_replace('credit', '', $method), '_');
                        $n = $suffix !== '' ? (int)$suffix : 0;
                        $code = 'full_debit_credit_' . $n;
                    }
                    break;
            }
        } else {
            switch ($method) {
                case 'khipu': $code = 'lat90_transfer_khipu'; break;
                case 'debit_credit_0': $code = 'lat90_debit_credit_0'; break;
                default:
                    // Fallback para códigos legacy
                    if ($method === 'debit') {
                        $code = 'lat90_debit_credit_0';
                    } elseif ($method === 'credit') {
                        $code = 'lat90_debit_credit_0';
                    }
                    break;
            }
        }

        // Log para debugging
        $this->logInfo('PaymentOrderService: Payment option code resolved', [
            'method' => $method,
            'code' => $code
        ]);

        if (!$code) { 
            $this->logWarning('PaymentOrderService: No payment option code found', [
                'method' => $method,
                'mode' => $mode
            ]);
            return null; 
        }

        $optionId = DB::table('payment_options')->where('code', $code)->value('id');
        
        // Log para debugging
        $this->logInfo('PaymentOrderService: Payment option lookup', [
            'code' => $code,
            'option_id' => $optionId
        ]);
        
        if (!$optionId) { 
            $this->logWarning('PaymentOrderService: Payment option not found in database', [
                'code' => $code
            ]);
            return null; 
        }
        
        $enabled = DB::table('program_payment_option')
            ->where('program_id', $programId)
            ->where('payment_option_id', $optionId)
            ->where('enabled', true)
            ->exists();
            
        // Log para debugging
        $this->logInfo('PaymentOrderService: Payment option enabled check', [
            'program_id' => $programId,
            'option_id' => $optionId,
            'enabled' => $enabled
        ]);
            
        return $enabled ? (int)$optionId : null;
    }

    /**
     * Calcular montos por participante
     */
    private function computeParticipantAmounts(Program $program, Participant $participant): array
    {
        // Calcular el precio del participante usando el helper
        $priceData = \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        $participantTotalAmount = $priceData['final_price'];

        // Calcular pagos aprobados previos
        $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $program->id);
            })
            ->where('status', 'approved')
            ->sum('amount');
        $paidAmount = round($paidAmount, 2);
        $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);
        
        return [$participantTotalAmount, $paidAmount, $participantBalance];
    }

    /**
     * Generar número de orden único
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $year = date('Y');
        $month = date('m');
        
        do {
            // Generar un número aleatorio de 6 dígitos
            $randomSequence = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $orderNumber = sprintf('%s-%s%s-%s', $prefix, $year, $month, $randomSequence);
            
            // Verificar que no exista ya en la base de datos
            $exists = Order::where('order_number', $orderNumber)->exists();
        } while ($exists);
        
        return $orderNumber;
    }
}
