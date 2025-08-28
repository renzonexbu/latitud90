<?php

namespace App\Services\Admin\Reports\Softland;

use App\Models\Payment;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SoftlandDataService
{
    /**
     * Genera los movimientos contables para Softland basado en los pagos
     */
    public function generateMovements(array $filters = []): Collection
    {
        // Obtener TODOS los pagos completed primero
        $payments = Payment::where('status', 'completed')
        ->when(isset($filters['dateFrom']), function ($query) use ($filters) {
            $query->whereDate('transaction_date', '>=', $filters['dateFrom']);
        })
        ->when(isset($filters['dateTo']), function ($query) use ($filters) {
            $query->whereDate('transaction_date', '<=', $filters['dateTo']);
        })
        ->when(isset($filters['programId']), function ($query) use ($filters) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('program_id', $filters['programId']);
            });
        })
        ->orderBy('transaction_date')
        ->get();

        $debitMovements = collect();  // Movimientos DEBE
        $creditMovements = collect(); // Movimientos HABER

        foreach ($payments as $payment) {
            // Solo verificar que tenga orden
            if (!$payment->order) {
                Log::warning('SoftlandDataService: Pago sin orden - SALTANDO', ['payment_id' => $payment->id]);
                continue;
            }
            
            // Verificar si es un reembolso por payment_option_id
            $isRefund = ($payment->payment_option_id == 19);
            
            Log::info('SoftlandDataService: Procesando pago', [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'payment_option_id' => $payment->payment_option_id,
                'is_refund' => $isRefund,
                'buy_order' => $payment->buy_order
            ]);
            
            if ($isRefund) {
                // Para reembolsos: separar DEBE y HABER
                $debitMovements->push($this->createRefundDebitMovement($payment));
                $creditMovements->push($this->createRefundCreditMovement($payment));
            } else {
                // Para pagos normales: separar DEBE y HABER
                $debitMovements->push($this->createDebitMovement($payment));
                $creditMovements->push($this->createCreditMovement($payment));
            }
        }
        
        // Combinar movimientos: primero todos los DEBE, luego todos los HABER
        $movements = $debitMovements->concat($creditMovements);

        Log::info('SoftlandDataService: Movimientos generados', [
            'total_payments' => $payments->count(),
            'total_debit_movements' => $debitMovements->count(),
            'total_credit_movements' => $creditMovements->count(),
            'total_movements' => $movements->count(),
            'organization' => 'DEBE primero, luego HABER'
        ]);

        return $movements;
    }

    /**
     * Crea el movimiento de cobro (DEBE)
     */
    private function createDebitMovement(Payment $payment): array
    {
        $participant = $payment->order->participant;
        $paymentOption = $payment->paymentOption;
        $participantProgram = $payment->order->participantProgram;
        
        return [
            // Información básica
            'codigo_plan_cuenta' => '1-1-02-010', // Cuenta de cobranzas
            'debe' => abs($payment->amount), // Siempre usar valor absoluto
            'haber' => 0,
            'descripcion_movimiento' => $this->formatDescription($payment, $participant, $paymentOption),
            'equivalencia_moneda' => 1,
            'monto_debe_moneda_adicional' => 0,
            'monto_haber_moneda_adicional' => 0,
            
            // Códigos
            'codigo_condicion_venta' => '',
            'codigo_vendedor' => '',
            'codigo_ubicacion' => '',
            'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '',
            'cantidad_instrumento_financiero' => 0,
            'codigo_detalle_gasto' => '',
            'cantidad_concepto_gasto' => 0,
            'codigo_centro_costo' => '',
            
            // Documentación
            'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '',
            'codigo_auxiliar' => $this->formatAuxiliaryCode($participantProgram),
            'tipo_documento' => $payment->document_type ?? 'B2',
            'nro_documento' => $payment->buy_order ?? $payment->id,
            'fecha_emision_docto' => $this->formatDate($payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDate($payment->transaction_date),
            'tipo_docto_referencia' => $payment->document_type ?? 'B2',
            'nro_docto_referencia' => $payment->buy_order ?? $payment->id,
            'nro_correlativo_interno' => $payment->buy_order ?? $payment->id,
            
            // Montos detalle libro
            'monto_1_detalle_libro' => 0, // NETO
            'monto_2_detalle_libro' => abs($payment->amount), // EXENTO (monto pagado)
            'monto_3_detalle_libro' => 0, // IVA
            'monto_4_detalle_libro' => 0, // ESPECIFICO
            'monto_5_detalle_libro' => 0,
            'monto_6_detalle_libro' => 0,
            'monto_7_detalle_libro' => 0,
            'monto_8_detalle_libro' => 0,
            'monto_9_detalle_libro' => 0,
            'monto_suma_detalle_libro' => abs($payment->amount),
            
            // Configuración
            'graba_detalle_libro' => 'S',
            'documento_nulo' => 'N',
            
            // Flujos de efectivo (vacíos por ahora)
            'codigo_flujo_efectivo_1' => '',
            'monto_flujo_1' => 0,
            'codigo_flujo_efectivo_2' => '',
            'monto_flujo_2' => 0,
            'codigo_flujo_efectivo_3' => '',
            'monto_flujo_3' => 0,
            'codigo_flujo_efectivo_4' => '',
            'monto_flujo_4' => 0,
            'codigo_flujo_efectivo_5' => '',
            'monto_flujo_5' => 0,
            'codigo_flujo_efectivo_6' => '',
            'monto_flujo_6' => 0,
            'codigo_flujo_efectivo_7' => '',
            'monto_flujo_7' => 0,
            'codigo_flujo_efectivo_8' => '',
            'monto_flujo_8' => 0,
            'codigo_flujo_efectivo_9' => '',
            'monto_flujo_9' => 0,
            'codigo_flujo_efectivo_10' => '',
            'monto_flujo_10' => 0,
            
            // Información adicional
            'numero_cuota_pago' => $payment->installments_number ?? 1,
            'numero_documento_desde' => '',
            'numero_documento_hasta' => '',
            
            // Centros de costo concepto presupuesto caja (vacíos por ahora)
            'centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => 0,
            'centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => 0,
            'centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => 0,
            'centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => 0,
            'centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => 0,
            'centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => 0,
            'centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => 0,
            'centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => 0,
            'centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => 0,
            'centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => 0,
        ];
    }

    /**
     * Crea el movimiento de ingreso (HABER)
     */
    private function createCreditMovement(Payment $payment): array
    {
        $participant = $payment->order->participant;
        $program = $payment->order->program;
        $paymentOption = $payment->paymentOption;
        
        return [
            // Información básica
            'codigo_plan_cuenta' => '3-1-01-021', // Cuenta de ingresos
            'debe' => 0,
            'haber' => abs($payment->amount), // Siempre usar valor absoluto
            'descripcion_movimiento' => $this->formatCreditDescription($payment, $participant, $program, $paymentOption),
            'equivalencia_moneda' => 1,
            'monto_debe_moneda_adicional' => 0,
            'monto_haber_moneda_adicional' => 0,
            
            // Códigos
            'codigo_condicion_venta' => '',
            'codigo_vendedor' => '',
            'codigo_ubicacion' => '',
            'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '',
            'cantidad_instrumento_financiero' => 0,
            'codigo_detalle_gasto' => '',
            'cantidad_concepto_gasto' => 0,
            'codigo_centro_costo' => '',
            
            // Documentación
            'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '',
            'codigo_auxiliar' => '',
            'tipo_documento' => 'E2-02-01', // Factura exenta
            'nro_documento' => $this->generateInvoiceNumber($payment),
            'fecha_emision_docto' => $this->formatDate($payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDate($payment->transaction_date),
            'tipo_docto_referencia' => $payment->document_type ?? 'B2',
            'nro_docto_referencia' => $payment->buy_order ?? $payment->id,
            'nro_correlativo_interno' => '',
            
            // Montos detalle libro
            'monto_1_detalle_libro' => 0, // NETO
            'monto_2_detalle_libro' => 0, // EXENTO
            'monto_3_detalle_libro' => 0, // IVA
            'monto_4_detalle_libro' => 0, // ESPECIFICO
            'monto_5_detalle_libro' => 0,
            'monto_6_detalle_libro' => 0,
            'monto_7_detalle_libro' => 0,
            'monto_8_detalle_libro' => 0,
            'monto_9_detalle_libro' => 0,
            'monto_suma_detalle_libro' => $payment->amount,
            
            // Configuración
            'graba_detalle_libro' => 'S',
            'documento_nulo' => 'N',
            
            // Flujos de efectivo (vacíos por ahora)
            'codigo_flujo_efectivo_1' => '',
            'monto_flujo_1' => 0,
            'codigo_flujo_efectivo_2' => '',
            'monto_flujo_2' => 0,
            'codigo_flujo_efectivo_3' => '',
            'monto_flujo_3' => 0,
            'codigo_flujo_efectivo_4' => '',
            'monto_flujo_4' => 0,
            'codigo_flujo_efectivo_5' => '',
            'monto_flujo_5' => 0,
            'codigo_flujo_efectivo_6' => '',
            'monto_flujo_6' => 0,
            'codigo_flujo_efectivo_7' => '',
            'monto_flujo_7' => 0,
            'codigo_flujo_efectivo_8' => '',
            'monto_flujo_8' => 0,
            'codigo_flujo_efectivo_9' => '',
            'monto_flujo_9' => 0,
            'codigo_flujo_efectivo_10' => '',
            'monto_flujo_10' => 0,
            
            // Información adicional
            'numero_cuota_pago' => $payment->installments_number ?? 1,
            'numero_documento_desde' => '',
            'numero_documento_hasta' => '',
            
            // Centros de costo concepto presupuesto caja (vacíos por ahora)
            'centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => 0,
            'centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => 0,
            'centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => 0,
            'centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => 0,
            'centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => 0,
            'centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => 0,
            'centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => 0,
            'centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => 0,
            'centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => 0,
            'centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => 0,
        ];
    }

    /**
     * Formatea la descripción del movimiento de cobro
     */
    private function formatDescription(Payment $payment, $participant, $paymentOption): string
    {
        $documentType = $payment->document_type ?? 'B2';
        $orderNumber = $payment->buy_order ?? $payment->id;
        $participantName = $participant ? ($participant->full_name ?? 'N/A') : 'PARTICIPANTE-NO-ENCONTRADO';
        $reportCode = $paymentOption ? ($paymentOption->report_code ?? '') : 'SIN-CODIGO';

        return "{$documentType} - {$orderNumber} - {$participantName} / {$reportCode}";
    }

    /**
     * Formatea la descripción del movimiento de ingreso
     */
    private function formatCreditDescription(Payment $payment, $participant, $program, $paymentOption): string
    {
        $invoiceNumber = $this->generateInvoiceNumber($payment);
        $programName = $program ? ($program->name ?? 'Programa') : 'PROGRAMA-NO-ENCONTRADO';
        $documentType = $payment->document_type ?? 'B2';
        $orderNumber = $payment->buy_order ?? $payment->id;

        return "{$invoiceNumber} / {$programName} - {$documentType} - {$orderNumber}";
    }

    /**
     * Formatea el código auxiliar (participant_program.enrollment_code)
     */
    private function formatAuxiliaryCode($participantProgram): string
    {
        if (!$participantProgram) {
            return '';
        }

        return $participantProgram->enrollment_code ?? '';
    }

    /**
     * Genera número de factura
     */
    private function generateInvoiceNumber(Payment $payment): string
    {
        // Usar el ID del pago como base para el número de factura
        return 'N' . str_pad($payment->id, 4, '0', STR_PAD_LEFT);
    }





    /**
     * Crea el movimiento de egreso para reembolso (DEBE)
     */
    private function createRefundDebitMovement(Payment $payment): array
    {
        $participant = $payment->order->participant ?? null;
        $paymentOption = $payment->paymentOption;
        $participantProgram = $payment->order->participantProgram ?? null;
        
        return [
            // Información básica
            'codigo_plan_cuenta' => '5-1-01-001', // Cuenta de gastos por servicios
            'debe' => abs($payment->amount), // Usar valor absoluto
            'haber' => 0,
            'descripcion_movimiento' => $this->formatRefundDescription($payment, $participant),
            'equivalencia_moneda' => 1,
            'monto_debe_moneda_adicional' => 0,
            'monto_haber_moneda_adicional' => 0,
            
            // Códigos
            'codigo_condicion_venta' => '',
            'codigo_vendedor' => '',
            'codigo_ubicacion' => '',
            'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '',
            'cantidad_instrumento_financiero' => 0,
            'codigo_detalle_gasto' => 'E2-02-01', // Detalle de gasto
            'cantidad_concepto_gasto' => 1,
            'codigo_centro_costo' => '',
            
            // Documentación
            'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '',
            'codigo_auxiliar' => $this->formatAuxiliaryCode($participantProgram),
            'tipo_documento' => 'NC', // Nota de crédito
            'nro_documento' => 'REEMB-' . ($payment->buy_order ?? $payment->id),
            'fecha_emision_docto' => $this->formatDate($payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDate($payment->transaction_date),
            'tipo_docto_referencia' => $payment->document_type ?? 'B2',
            'nro_docto_referencia' => $payment->buy_order ?? $payment->id,
            'nro_correlativo_interno' => $payment->buy_order ?? $payment->id,
            
            // Montos detalle libro
            'monto_1_detalle_libro' => 0, // NETO
            'monto_2_detalle_libro' => abs($payment->amount), // EXENTO (monto del reembolso)
            'monto_3_detalle_libro' => 0, // IVA
            'monto_4_detalle_libro' => 0, // ESPECIFICO
            'monto_5_detalle_libro' => 0,
            'monto_6_detalle_libro' => 0,
            'monto_7_detalle_libro' => 0,
            'monto_8_detalle_libro' => 0,
            'monto_9_detalle_libro' => 0,
            'monto_suma_detalle_libro' => abs($payment->amount),
            
            // Configuración
            'graba_detalle_libro' => 'S',
            'documento_nulo' => 'N',
            
            // Flujos de efectivo (vacíos por ahora)
            'codigo_flujo_efectivo_1' => '',
            'monto_flujo_1' => 0,
            'codigo_flujo_efectivo_2' => '',
            'monto_flujo_2' => 0,
            'codigo_flujo_efectivo_3' => '',
            'monto_flujo_3' => 0,
            'codigo_flujo_efectivo_4' => '',
            'monto_flujo_4' => 0,
            'codigo_flujo_efectivo_5' => '',
            'monto_flujo_5' => 0,
            'codigo_flujo_efectivo_6' => '',
            'monto_flujo_6' => 0,
            'codigo_flujo_efectivo_7' => '',
            'monto_flujo_7' => 0,
            'codigo_flujo_efectivo_8' => '',
            'monto_flujo_8' => 0,
            'codigo_flujo_efectivo_9' => '',
            'monto_flujo_9' => 0,
            'codigo_flujo_efectivo_10' => '',
            'monto_flujo_10' => 0,
            
            // Información adicional
            'numero_cuota_pago' => $payment->installments_number ?? 1,
            'numero_documento_desde' => '',
            'numero_documento_hasta' => '',
            
            // Centros de costo concepto presupuesto caja (vacíos por ahora)
            'centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => 0,
            'centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => 0,
            'centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => 0,
            'centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => 0,
            'centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => 0,
            'centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => 0,
            'centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => 0,
            'centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => 0,
            'centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => 0,
            'centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => 0,
        ];
    }

    /**
     * Crea el movimiento de pasivo para reembolso (HABER)
     */
    private function createRefundCreditMovement(Payment $payment): array
    {
        $participant = $payment->order->participant ?? null;
        $program = $payment->order->program ?? null;
        $paymentOption = $payment->paymentOption;
        
        return [
            // Información básica
            'codigo_plan_cuenta' => '2-1-01-001', // Cuenta de pasivos corrientes
            'debe' => 0,
            'haber' => abs($payment->amount), // Usar valor absoluto
            'descripcion_movimiento' => $this->formatRefundCreditDescription($payment, $participant, $program),
            'equivalencia_moneda' => 1,
            'monto_debe_moneda_adicional' => 0,
            'monto_haber_moneda_adicional' => 0,
            
            // Códigos
            'codigo_condicion_venta' => '',
            'codigo_vendedor' => '',
            'codigo_ubicacion' => '',
            'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '',
            'cantidad_instrumento_financiero' => 0,
            'codigo_detalle_gasto' => '',
            'cantidad_concepto_gasto' => 0,
            'codigo_centro_costo' => '',
            
            // Documentación
            'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '',
            'codigo_auxiliar' => '',
            'tipo_documento' => 'NC', // Nota de crédito
            'nro_documento' => 'REEMB-' . ($payment->buy_order ?? $payment->id),
            'fecha_emision_docto' => $this->formatDate($payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDate($payment->transaction_date),
            'tipo_docto_referencia' => $payment->document_type ?? 'B2',
            'nro_docto_referencia' => $payment->buy_order ?? $payment->id,
            'nro_correlativo_interno' => '',
            
            // Montos detalle libro
            'monto_1_detalle_libro' => 0, // NETO
            'monto_2_detalle_libro' => 0, // EXENTO
            'monto_3_detalle_libro' => 0, // IVA
            'monto_4_detalle_libro' => 0, // ESPECIFICO
            'monto_5_detalle_libro' => 0,
            'monto_6_detalle_libro' => 0,
            'monto_7_detalle_libro' => 0,
            'monto_8_detalle_libro' => 0,
            'monto_9_detalle_libro' => 0,
            'monto_suma_detalle_libro' => abs($payment->amount),
            
            // Configuración
            'graba_detalle_libro' => 'S',
            'documento_nulo' => 'N',
            
            // Flujos de efectivo (vacíos por ahora)
            'codigo_flujo_efectivo_1' => '',
            'monto_flujo_1' => 0,
            'codigo_flujo_efectivo_2' => '',
            'monto_flujo_2' => 0,
            'codigo_flujo_efectivo_3' => '',
            'monto_flujo_3' => 0,
            'codigo_flujo_efectivo_4' => '',
            'monto_flujo_4' => 0,
            'codigo_flujo_efectivo_5' => '',
            'monto_flujo_5' => 0,
            'codigo_flujo_efectivo_6' => '',
            'monto_flujo_6' => 0,
            'codigo_flujo_efectivo_7' => '',
            'monto_flujo_7' => 0,
            'codigo_flujo_efectivo_8' => '',
            'monto_flujo_8' => 0,
            'codigo_flujo_efectivo_9' => '',
            'monto_flujo_9' => 0,
            'codigo_flujo_efectivo_10' => '',
            'monto_flujo_10' => 0,
            
            // Información adicional
            'numero_cuota_pago' => $payment->installments_number ?? 1,
            'numero_documento_desde' => '',
            'numero_documento_hasta' => '',
            
            // Centros de costo concepto presupuesto caja (vacíos por ahora)
            'centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => 0,
            'centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => 0,
            'centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => 0,
            'centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => 0,
            'centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => 0,
            'centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => 0,
            'centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => 0,
            'centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => 0,
            'centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => 0,
            'centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => 0,
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => 0,
        ];
    }

    /**
     * Formatea la descripción del reembolso (DEBE)
     */
    private function formatRefundDescription(Payment $payment, $participant): string
    {
        $documentType = $payment->document_type ?? 'B2';
        $orderNumber = $payment->buy_order ?? $payment->id;
        $participantName = $participant ? ($participant->full_name ?? 'N/A') : 'PARTICIPANTE-NO-ENCONTRADO';
        $gatewayResponse = $payment->gateway_response ?? [];
        $refundNote = $gatewayResponse['note'] ?? $gatewayResponse['notes'] ?? 'Reembolso';

        return "REEMBOLSO - {$refundNote} - {$documentType} - {$orderNumber} - {$participantName}";
    }

    /**
     * Formatea la descripción del pasivo por reembolso (HABER)
     */
    private function formatRefundCreditDescription(Payment $payment, $participant, $program): string
    {
        $refundNumber = 'REEMB-' . ($payment->buy_order ?? $payment->id);
        $programName = $program ? ($program->name ?? 'Programa') : 'PROGRAMA-NO-ENCONTRADO';
        $documentType = $payment->document_type ?? 'B2';
        $orderNumber = $payment->buy_order ?? $payment->id;
        $gatewayResponse = $payment->gateway_response ?? [];
        $refundNote = $gatewayResponse['note'] ?? $gatewayResponse['notes'] ?? 'Reembolso';

        return "{$refundNumber} / {$programName} - REEMBOLSO {$refundNote} - {$documentType} - {$orderNumber}";
    }

    /**
     * Formatea fecha en formato DD/MM/AAAA
     */
    private function formatDate($date): string
    {
        if (!$date) {
            return '';
        }

        if ($date instanceof Carbon) {
            return $date->format('d/m/Y');
        }

        return Carbon::parse($date)->format('d/m/Y');
    }
}
