<?php

namespace App\Services\Client;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Participant;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateOrderService
{
    public function createOrder($programId, $rut, $paymentData, $formData)
    {
        try {
            DB::beginTransaction();

            // Buscar el participante por RUT
            $participant = Participant::where('document_number', $rut)->first();
            if (!$participant) {
                throw new \Exception('Participante no encontrado');
            }

            // Buscar el programa
            $program = Program::findOrFail($programId);

            // Calcular montos
            $totalAmount = $program->trip_price;
            $discount = 0; // Por ahora sin descuentos
            $finalAmount = $totalAmount - $discount;

            // Determinar número total de cuotas
            $totalInstallments = $paymentData['paymentType'] === 'monthly' ? $paymentData['installments'] : 1;

            // Crear la orden principal
            $order = Order::create([
                'participant_id' => $participant->id,
                'program_id' => $program->id,
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'final_amount' => $finalAmount,
                'total_installments' => $totalInstallments,
                'payment_type' => $paymentData['paymentType'],
                'status' => 'pending',
                'order_number' => $this->generateOrderNumber(),
                'notes' => 'Orden creada desde el flujo de pago'
            ]);

            // Crear el detalle de la orden (primera cuota)
            $this->createOrderDetail($order, $paymentData, $formData, 1, $finalAmount);

            // Si es pago mensual, crear las cuotas adicionales
            if ($paymentData['paymentType'] === 'monthly' && $totalInstallments > 1) {
                $installmentAmount = $finalAmount / $totalInstallments;
                
                for ($i = 2; $i <= $totalInstallments; $i++) {
                    $dueDate = now()->addMonths($i - 1);
                    $this->createOrderDetail($order, $paymentData, $formData, $i, $installmentAmount, $dueDate);
                }
            }

            DB::commit();

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'participant_rut' => $rut,
                'program_id' => $programId,
                'total_amount' => $finalAmount,
                'installments' => $totalInstallments
            ]);

            return [
                'success' => true,
                'order' => $order,
                'order_detail' => $order->orderDetails->first()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order', [
                'error' => $e->getMessage(),
                'program_id' => $programId,
                'rut' => $rut
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function createOrderDetail($order, $paymentData, $formData, $installmentNumber, $amount, $dueDate = null)
    {
        // Determinar el método de pago y modo de pago según la selección
        $paymentMethodMapping = [
            'debit' => 1, // Asumiendo que 1 es tarjeta de débito
            'credit' => 2, // Asumiendo que 2 es tarjeta de crédito
            'khipu' => 3, // Asumiendo que 3 es transferencia Khipu
        ];

        $paymentModeMapping = [
            'total' => 1, // Pago total
            'monthly' => 2, // Pago mensual
        ];

        // Determinar el gateway de pago según el método
        $gatewayMapping = [
            'debit' => 1, // Transbank
            'credit' => 1, // Transbank
            'khipu' => 2, // Khipu
        ];

        // Mapear las claves del frontend (camelCase) a las claves del backend (snake_case)
        $mappedFormData = [
            'name' => $formData['name'] ?? null,
            'email' => $formData['email'] ?? null,
            'country' => $formData['countryName'] ?? null,
            'region' => $formData['regionName'] ?? null,
            'city' => $formData['cityName'] ?? null,
            'code_phone' => $formData['code_phone'] ?? null,
            'phone' => $formData['phone'] ?? null,
            'document_type' => $formData['documentType'] ?? null,
            'document_number' => $formData['documentNumber'] ?? null,
            'terms_accepted' => $formData['termsAccepted'] ?? false,
            'marketing_accepted' => $formData['marketingAccepted'] ?? false,
        ];

        return OrderDetail::create([
            'order_id' => $order->id,
            'payment_method_id' => $paymentMethodMapping[$paymentData['paymentMethod']] ?? 1,
            'payment_mode_id' => $paymentModeMapping[$paymentData['paymentType']] ?? 1,
            'payment_gateway_id' => $gatewayMapping[$paymentData['paymentMethod']] ?? 1,
            
            // Datos del comprador
            'name' => $mappedFormData['name'],
            'email' => $mappedFormData['email'],
            'country' => $mappedFormData['country'],
            'region' => $mappedFormData['region'],
            'city' => $mappedFormData['city'],
            'code_phone' => $mappedFormData['code_phone'],
            'phone' => $mappedFormData['phone'],
            'document_type' => $mappedFormData['document_type'],
            'document_number' => $mappedFormData['document_number'],
            
            // Dirección de facturación (por ahora usando los mismos datos)
            'billing_address' => $formData['billing_address'] ?? null,
            'billing_city' => $mappedFormData['city'],
            'billing_country' => $mappedFormData['country'],
            'billing_postal_code' => $formData['billing_postal_code'] ?? null,
            
            // Acuerdos
            'terms_accepted' => $mappedFormData['terms_accepted'],
            'marketing_accepted' => $mappedFormData['marketing_accepted'],
            'terms_accepted_confirmation' => $paymentData['termsAccepted'] ?? false,
            
            // Información de la cuota
            'installment_number' => $installmentNumber,
            'amount' => $amount,
            'due_date' => $dueDate ?? now(),
            'is_paid' => false,
            'status' => 'pending',
        ]);
    }

    private function generateOrderNumber()
    {
        $prefix = 'ORD';
        $year = date('Y');
        $month = date('m');
        $sequence = Order::whereYear('created_at', $year)
                        ->whereMonth('created_at', $month)
                        ->count() + 1;
        
        return sprintf('%s-%s%s-%06d', $prefix, $year, $month, $sequence);
    }
}
