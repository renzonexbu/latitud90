<?php

namespace App\Services\Admin\Reports\Softland;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SoftlandB2DataService
{
    /**
     * Genera los movimientos contables para exportar a Softland (solo B2)
     */
    public function generateMovements(array $filters = []): Collection
    {
        Log::info('SoftlandB2DataService: Iniciando generación de movimientos B2', $filters);

        // Construir query base
        $query = Payment::with([
            'order.participant.emergencyContacts',
            'order.program',
            'order.orderDetails',
            'paymentOption'
        ])
        ->where('status', 'completed')
        ->where('document_type', 'B2') // Solo B2
        ->whereHas('paymentOption', function ($q) {
            $q->where('mode', '!=', 'presential');
        });

        // Aplicar filtros de fecha si están presentes
        if (isset($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        $payments = $query->get();

        Log::info('SoftlandB2DataService: Pagos B2 encontrados', [
            'total_payments' => $payments->count()
        ]);

        $debitMovements = collect();
        $creditMovements = collect();

        /** @var Payment $payment */
        foreach ($payments as $payment) {
            Log::info('SoftlandB2DataService: Procesando pago B2', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'document_type' => $payment->document_type
            ]);

            // Crear movimiento DEBE (cobro)
            $debitMovements->push($this->createDebitMovement($payment));
            
            // Crear movimiento HABER (ingreso)
            $creditMovements->push($this->createCreditMovement($payment));
        }

        // Combinar movimientos: primero DEBE, luego HABER
        $movements = $debitMovements->concat($creditMovements);

        Log::info('SoftlandB2DataService: Movimientos B2 generados', [
            'total_payments' => $payments->count(),
            'total_debit_movements' => $debitMovements->count(),
            'total_credit_movements' => $creditMovements->count(),
            'total_movements' => $movements->count()
        ]);

        return $movements;
    }

    /**
     * Crea el movimiento de cobro B2 (DEBE)
     */
    private function createDebitMovement(Payment $payment): array
    {
        $participant = $payment->order->participant;
        $paymentOption = $payment->paymentOption;
        
        // Obtener el nombre del comprador
        $buyerName = '';
        $orderDetail = $payment->order->orderDetails()->first();
        if ($orderDetail && $orderDetail->name) {
            $buyerName = $orderDetail->name;
        } elseif ($participant) {
            $buyerName = $participant->name;
        }
        
        return [
            // Información básica
            'codigo_plan_cuenta' => '1-1-02-014', // Cuenta específica para B2
            'debe' => (int) abs($payment->amount), // Sin decimales
            'haber' => 0, // Vacío para DEBE
            'descripcion_movimiento' => $buyerName, // Nombre del comprador
            'equivalencia_moneda' => '', // Columna 5
            'monto_debe_moneda_adicional' => '', // Columna 6
            'monto_haber_moneda_adicional' => '', // Columna 7
            
            // Códigos (columnas 8-16)
            'codigo_condicion_venta' => '', // Columna 8
            'codigo_vendedor' => '', // Columna 9
            'codigo_ubicacion' => '', // Columna 10
            'codigo_concepto_caja' => '', // Columna 11
            'codigo_instrumento_financiero' => '', // Columna 12
            'cantidad_instrumento_financiero' => '', // Columna 13
            'codigo_detalle_gasto' => '', // Columna 14
            'cantidad_concepto_gasto' => '', // Columna 15
            'codigo_centro_costo' => '', // Columna 16
            
            // Documentación (columnas 17-26)
            'tipo_docto_conciliacion' => '', // Columna 17
            'nro_docto_conciliacion' => '', // Columna 18
            'codigo_auxiliar' => $this->formatAuxiliaryCode($payment), // Columna 19 - mismo que otros
            'tipo_documento' => $paymentOption->report_code ?? '', // Columna 20 - payment_option.report_code
            'nro_documento' => $this->getDocumentNumber($payment), // Columna 21 - Número de autorización o external_payment_id para Khipu
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna 22
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna 23
            'tipo_docto_referencia' => $paymentOption->report_code ?? '', // Columna 24 - payment_option.report_code
            'nro_docto_referencia' => $this->getDocumentNumber($payment), // Columna 25 - order number
            'fecha_docto_referencia' => '', // Columna 26 - Debe estar vacía
            
            // Montos detalle libro (columnas 27-36)
            'monto_1_detalle_libro' => '', // Columna 27
            'monto_2_detalle_libro' => '', // Columna 28 - Debe estar vacía
            'monto_3_detalle_libro' => '', // Columna 29
            'monto_4_detalle_libro' => '', // Columna 30
            'monto_5_detalle_libro' => '', // Columna 31
            'monto_6_detalle_libro' => '', // Columna 32
            'monto_7_detalle_libro' => '', // Columna 33
            'monto_8_detalle_libro' => '', // Columna 34
            'monto_9_detalle_libro' => '', // Columna 35
            'monto_suma_detalle_libro' => '', // Columna 36 - Debe estar vacía
            
            // Configuración (columnas 37-38)
            'graba_detalle_libro' => '', // Columna 37
            'codigo_moneda_adicional' => '', // Columna 38
            
            // Flujos de efectivo (columnas 39-58)
            'codigo_flujo_efectivo_1' => '', // Columna 39
            'monto_flujo_1' => '', // Columna 40
            'codigo_flujo_efectivo_2' => '', // Columna 41
            'monto_flujo_2' => '', // Columna 42
            'codigo_flujo_efectivo_3' => '', // Columna 43
            'monto_flujo_3' => '', // Columna 44
            'codigo_flujo_efectivo_4' => '', // Columna 45
            'monto_flujo_4' => '', // Columna 46
            'codigo_flujo_efectivo_5' => '', // Columna 47
            'monto_flujo_5' => '', // Columna 48
            'codigo_flujo_efectivo_6' => '', // Columna 49
            'monto_flujo_6' => '', // Columna 50
            'codigo_flujo_efectivo_7' => '', // Columna 51
            'monto_flujo_7' => '', // Columna 52
            'codigo_flujo_efectivo_8' => '', // Columna 53
            'monto_flujo_8' => '', // Columna 54
            'codigo_flujo_efectivo_9' => '', // Columna 55
            'monto_flujo_9' => '', // Columna 56
            'codigo_flujo_efectivo_10' => '', // Columna 57
            'monto_flujo_10' => '', // Columna 58
            
            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59
            'numero_documento_desde' => '', // Columna 60
            'numero_documento_hasta' => '', // Columna 61
            
            // Centros de costo (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91
        ];
    }

    /**
     * Crea el movimiento de ingreso B2 (HABER)
     */
    private function createCreditMovement(Payment $payment): array
    {
        $participant = $payment->order->participant;
        $paymentOption = $payment->paymentOption;
        
        // Obtener el nombre del comprador (solo nombre, no documento)
        $buyerName = '';
        $orderDetail = $payment->order->orderDetails()->first();
        if ($orderDetail && !empty($orderDetail->name)) {
            $buyerName = trim($orderDetail->name);
        } elseif ($participant && !empty($participant->name)) {
            $buyerName = trim($participant->name);
        }
        
        // Formato de descripción: "B2 - boleta_number - buyer_name / report_code"
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);
        $reportCode = $paymentOption->report_code ?? '';
        $description = "B2 - {$boletaNumber} - {$buyerName} / {$reportCode}";
        
        return [
            // Información básica
            'codigo_plan_cuenta' => '1-1-02-010', // Cuenta específica para B2 HABER
            'debe' => 0, // Vacío para HABER
            'haber' => (int) abs($payment->amount), // Sin decimales
            'descripcion_movimiento' => $description, // Formato específico
            'equivalencia_moneda' => '', // Columna 5
            'monto_debe_moneda_adicional' => '', // Columna 6
            'monto_haber_moneda_adicional' => '', // Columna 7
            
            // Códigos (columnas 8-16)
            'codigo_condicion_venta' => '', // Columna 8
            'codigo_vendedor' => '', // Columna 9
            'codigo_ubicacion' => '', // Columna 10
            'codigo_concepto_caja' => '', // Columna 11
            'codigo_instrumento_financiero' => '', // Columna 12
            'cantidad_instrumento_financiero' => '', // Columna 13
            'codigo_detalle_gasto' => '', // Columna 14
            'cantidad_concepto_gasto' => '', // Columna 15
            'codigo_centro_costo' => '', // Columna 16 - vacío
            
            // Documentación (columnas 17-26)
            'tipo_docto_conciliacion' => '', // Columna 17
            'nro_docto_conciliacion' => '', // Columna 18
            'codigo_auxiliar' => $this->formatAuxiliaryCode($payment), // Columna 19 - mismo que otros
            'tipo_documento' => $paymentOption->report_code ?? '', // Columna 20 - payment_option.report_code
            'nro_documento' => $this->getDocumentNumber($payment), // Columna 21 - Número de autorización o external_payment_id para Khipu
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna 22 - fecha pago
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna 23 - fecha pago
            'tipo_docto_referencia' => 'B2', // Columna 24 - B2
            'nro_docto_referencia' => $payment->bsale_number ?? ($payment->buy_order ?? $payment->id), // Columna 25 - bsale boleta number
            'fecha_docto_referencia' => '', // Columna 26
            
            // Montos detalle libro (columnas 27-36)
            'monto_1_detalle_libro' => '', // Columna 27
            'monto_2_detalle_libro' => '', // Columna 28 - debe estar vacía
            'monto_3_detalle_libro' => '', // Columna 29
            'monto_4_detalle_libro' => '', // Columna 30
            'monto_5_detalle_libro' => '', // Columna 31
            'monto_6_detalle_libro' => '', // Columna 32
            'monto_7_detalle_libro' => '', // Columna 33
            'monto_8_detalle_libro' => '', // Columna 34
            'monto_9_detalle_libro' => '', // Columna 35
            'monto_suma_detalle_libro' => '', // Columna 36 - debe estar vacía
            
            // Configuración (columnas 37-38)
            'graba_detalle_libro' => '', // Columna 37
            'codigo_moneda_adicional' => '', // Columna 38
            
            // Flujos de efectivo (columnas 39-58)
            'codigo_flujo_efectivo_1' => '', // Columna 39
            'monto_flujo_1' => '', // Columna 40
            'codigo_flujo_efectivo_2' => '', // Columna 41
            'monto_flujo_2' => '', // Columna 42
            'codigo_flujo_efectivo_3' => '', // Columna 43
            'monto_flujo_3' => '', // Columna 44
            'codigo_flujo_efectivo_4' => '', // Columna 45
            'monto_flujo_4' => '', // Columna 46
            'codigo_flujo_efectivo_5' => '', // Columna 47
            'monto_flujo_5' => '', // Columna 48
            'codigo_flujo_efectivo_6' => '', // Columna 49
            'monto_flujo_6' => '', // Columna 50
            'codigo_flujo_efectivo_7' => '', // Columna 51
            'monto_flujo_7' => '', // Columna 52
            'codigo_flujo_efectivo_8' => '', // Columna 53
            'monto_flujo_8' => '', // Columna 54
            'codigo_flujo_efectivo_9' => '', // Columna 55
            'monto_flujo_9' => '', // Columna 56
            'codigo_flujo_efectivo_10' => '', // Columna 57
            'monto_flujo_10' => '', // Columna 58
            
            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59
            'numero_documento_desde' => '', // Columna 60
            'numero_documento_hasta' => '', // Columna 61
            
            // Centros de costo (columnas 62-91) - todas vacías para HABER
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91
        ];
    }

    /**
     * Formatea la descripción del movimiento de cobro (DEBE)
     */
    private function formatDescription(Payment $payment, $participant, $paymentOption): string
    {
        $documentType = $payment->document_type ?? 'B2';
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);
        $participantName = $participant ? $participant->name : 'Sin participante';
        $paymentMethod = $paymentOption ? $paymentOption->name : 'Sin método';
        
        return "Cobro {$documentType} {$boletaNumber} - {$participantName} - {$paymentMethod}";
    }

    /**
     * Formatea la descripción del movimiento de ingreso (HABER)
     */
    private function formatCreditDescription(Payment $payment, $participant, $program, $paymentOption): string
    {
        $programCode = $program ? $program->code : 'Sin programa';
        $participantDocument = $participant ? $participant->document_number : 'Sin documento';
        $paymentMethod = $paymentOption ? $paymentOption->name : 'Sin método';
        
        return "Ingreso {$programCode} - {$participantDocument} - {$paymentMethod}";
    }

    /**
     * Formatea el código auxiliar (mismo formato que SoftlandAuxiliaresService)
     */
    private function formatAuxiliaryCode(Payment $payment): string
    {
        $orderDetail = $payment->order->orderDetails()->first();
        if (!$orderDetail || !$orderDetail->document_number) {
            return '';
        }
        
        // Ensure relationships are loaded
        $orderDetail->load(['documentType']);
        
        $documentNumber = $orderDetail->document_number;
        
        // Si es pasaporte, usar tal como está en mayúsculas
        if ($orderDetail->documentType && is_object($orderDetail->documentType) && $orderDetail->documentType->name === 'PASAPORTE') {
            return strtoupper($documentNumber);
        }
        
        // Si es RUT, quitar puntos y guiones y convertir a mayúsculas
        if ($orderDetail->documentType && is_object($orderDetail->documentType) && $orderDetail->documentType->name === 'RUT') {
            return strtoupper(preg_replace('/[^0-9kK]/', '', $documentNumber));
        }
        
        // Para otros tipos de documento, usar tal como está en mayúsculas
        return strtoupper($documentNumber);
    }

    /**
     * Obtiene el número de documento según el método de pago
     * Para Khipu usa external_payment_id, de lo contrario usa authorization_code
     * 
     * @param Payment $payment
     * @return string
     */
    private function getDocumentNumber($payment): string
    {
        // Si es pago con Khipu, usar external_payment_id si existe
        if ($payment->paymentOption && $payment->paymentOption->report_code === 'KP' && !empty($payment->external_payment_id)) {
            return $payment->external_payment_id;
        }
        
        // Si no es Khipu o no tiene external_payment_id, usar authorization_code
        return $payment->authorization_code ?? ($payment->buy_order ?? (string)$payment->id);
    }

    private function formatDateDDMMYYYY($date): string
    {
        if (!$date) {
            return '';
        }

        if ($date instanceof Carbon) {
            return $date->format('d-m-Y');
        }

        return Carbon::parse($date)->format('d-m-Y');
    }
}
