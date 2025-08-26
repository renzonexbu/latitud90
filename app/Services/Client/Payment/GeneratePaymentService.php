<?php

namespace App\Services\Client\Payment;

use App\Models\Program;
use App\Models\Payment;
use App\Models\Order;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;

class GeneratePaymentService
{
    use SystemLogging;
    public function getPaymentDetails($programId, $participantId = null, $rut = null)
    {
        $program = Program::find($programId);

        if (!$program) {
            return null;
        }

        // Si hay RUT, intentar cargar próxima cuota activa (due_date) de orden mensual
        $activeInstallment = null;
        if ($rut) {
            $order = Order::whereHas('participant', function($q) use ($rut) {
                    $q->where('document_number', preg_replace('/[.-]/', '', $rut));
                })
                ->where('program_id', $programId)
                ->where('payment_type', 'monthly')
                ->latest('id')
                ->first();
            if ($order) {
                $next = $order->orderDetails()
                    ->where('is_paid', false)
                    ->orderBy('due_date')
                    ->first();
                if ($next) {
                    $activeInstallment = [
                        'number' => (int) $next->installment_number,
                        'due_date' => optional($next->due_date)->toDateString(),
                        'amount' => (float) $next->amount,
                    ];
                }
            }
        }

        return [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'destination' => $program->destination,
                'trip_price' => $program->trip_price,
                'departure_date' => $program->departure_date,
                'final_payment_date' => $program->final_payment_date,
                'max_installments' => $program->max_installments,
                'active_installment' => $activeInstallment,
            ],
            'participant' => $participantId ? [
                'id' => $participantId,
                // Aquí puedes agregar más datos del participante si es necesario
            ] : null,
            'payment_options' => [
                'total' => [
                    'debit' => 'Pago con Tarjeta de Debito',
                    'credit' => 'Pago con Tarjeta de Credito',
                    'khipu' => 'Pago con Transferencia Khipu'
                ],
                'monthly' => [
                    'debit' => 'Pago con Tarjeta de Debito',
                    'credit' => 'Pago con Tarjeta de Credito',
                    'khipu' => 'Pago con Transferencia Khipu'
                ]
            ]
        ];
    }

    public function generatePayment($programId, $paymentData)
    {
        try {
            DB::beginTransaction();

            $program = Program::find($programId);
            
            if (!$program) {
                return ['success' => false, 'message' => 'Programa no encontrado'];
            }

            // Determinar payment_method_id y payment_mode_id basado en la selección
            $paymentMethodId = $this->getPaymentMethodId($paymentData['payment_method']);
            $paymentModeId = $this->getPaymentModeId($paymentData['payment_type']);

            // Crear la orden
            $order = Order::create([
                'participant_id' => auth()->id() ?? 1, // Temporal, debería venir del usuario autenticado
                'payment_method_id' => $paymentMethodId,
                'payment_mode_id' => $paymentModeId,
                'payment_gateway_id' => null, // Se llena cuando se procesa el pago
                
                // Datos del comprador (temporales, deberían venir del formulario)
                'buyer_first_name' => 'Usuario',
                'buyer_last_name' => 'Temporal',
                'buyer_email' => 'usuario@temporal.com',
                'buyer_phone' => null,
                'buyer_document_type' => null,
                'buyer_document_number' => null,
                
                // Dirección de facturación (temporal)
                'billing_address' => null,
                'billing_city' => null,
                'billing_country' => null,
                'billing_postal_code' => null,
                
                // Información de la orden
                'price' => $program->trip_price,
                'discount' => 0,
                'total' => $program->trip_price,
                'status' => 'pending',
                'notes' => "Programa: {$program->name} - {$program->destination}",
                'order_number' => 'ORD-' . time() . '-' . rand(1000, 9999)
            ]);

            // Crear el pago
            $payment = Payment::create([
                'order_id' => $order->id,
                'buy_order' => $order->order_number,
                'amount' => $program->trip_price,
                'status' => 'pending',
                'installments_number' => $paymentData['installments'] ?? 1
            ]);

            DB::commit();

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'message' => 'Pago generado exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Error generando pago: ' . $e->getMessage(), [], $e);
            
            return [
                'success' => false,
                'message' => 'Error al generar el pago'
            ];
        }
    }

    private function getPaymentMethodId($paymentMethod)
    {
        // Mapear los métodos de pago a los IDs de la base de datos
        $methodMap = [
            'debit' => 1, // Asumiendo que 1 es tarjeta de débito
            'credit' => 2, // Asumiendo que 2 es tarjeta de crédito
            'khipu' => 3, // Asumiendo que 3 es transferencia Khipu
        ];
        
        return $methodMap[$paymentMethod] ?? 1;
    }

    private function getPaymentModeId($paymentType)
    {
        // Mapear los tipos de pago a los IDs de la base de datos
        $modeMap = [
            'total' => 1, // Asumiendo que 1 es pago total
            'monthly' => 2, // Asumiendo que 2 es pago mensual
        ];
        
        return $modeMap[$paymentType] ?? 1;
    }
}
