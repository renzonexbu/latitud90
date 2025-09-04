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
        // Obtener SOLO pagos completados que generaron boleta (B2)
        $payments = Payment::with(['paymentOption', 'order.participant', 'order.program', 'order.participantProgram'])
        ->where('status', 'completed')
        ->where('document_type', 'B2')
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
            
            // Verificar si es un reembolso por payment_option_code
            $isRefund = ($payment->paymentOption && $payment->paymentOption->code === 'refund_credit_note');
            
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
            'haber' => '', // Vacío para DEBE
            'descripcion_movimiento' => $this->formatDescription($payment, $participant, $paymentOption),
            'equivalencia_moneda' => '', // Columna E - vacía
            'monto_debe_moneda_adicional' => '', // Columna F - vacía
            'monto_haber_moneda_adicional' => '', // Columna G - vacía
            
            // Códigos (columnas H-M vacías)
            'codigo_condicion_venta' => '', // Columna H - vacía
            'codigo_vendedor' => '', // Columna I - vacía
            'codigo_ubicacion' => '', // Columna J - vacía
            'codigo_concepto_caja' => '', // Columna K - vacía
            'codigo_instrumento_financiero' => '', // Columna L - vacía
            'cantidad_instrumento_financiero' => '', // Columna M - vacía
            'codigo_detalle_gasto' => '', // Columna N - vacía
            'cantidad_concepto_gasto' => '', // Columna O - vacía
            'codigo_centro_costo' => '', // Columna P - vacía para DEBE
            
            // Documentación
            'tipo_docto_conciliacion' => '', // Columna Q - vacía
            'nro_docto_conciliacion' => '', // Columna R - vacía
            'codigo_auxiliar' => $this->formatAuxiliaryCode($participant), // Columna S - RUT sin puntos/guiones
            'tipo_documento' => $payment->document_type ?? 'B2', // Columna T - tipo documento
            'nro_documento' => $payment->bsale_number ?? ($payment->buy_order ?? $payment->id), // Columna U - bsale_number
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna W - formato DD-MM-YYYY
            'tipo_docto_referencia' => $payment->document_type ?? 'B2', // Columna X - B2 para DEBE
            'nro_docto_referencia' => $payment->bsale_number ?? ($payment->buy_order ?? $payment->id), // Columna Y - bsale_number
            'nro_correlativo_interno' => '', // Columna Z - vacía
            
            // Montos detalle libro
            'monto_1_detalle_libro' => '', // Columna AA - vacía
            'monto_2_detalle_libro' => abs($payment->amount), // Columna AB - mismo monto del debe
            'monto_3_detalle_libro' => '', // Columna AC - vacía
            'monto_4_detalle_libro' => '', // Columna AD - vacía
            'monto_5_detalle_libro' => '', // Columna AE - vacía
            'monto_6_detalle_libro' => '', // Columna AF - vacía
            'monto_7_detalle_libro' => '', // Columna AG - vacía
            'monto_8_detalle_libro' => '', // Columna AH - vacía
            'monto_9_detalle_libro' => '', // Columna AI - vacía
            'monto_suma_detalle_libro' => abs($payment->amount), // Columna AJ - mismo monto del debe
            
            // Configuración
            'graba_detalle_libro' => 'S', // Columna AK - 'S' para DEBE
            'documento_nulo' => '', // Columna AL - vacía
            
            // Flujos de efectivo (todas vacías)
            'codigo_flujo_efectivo_1' => '', // Columna AM - vacía
            'monto_flujo_1' => '', // Columna AN - vacía
            'codigo_flujo_efectivo_2' => '', // Columna AO - vacía
            'monto_flujo_2' => '', // Columna AP - vacía
            'codigo_flujo_efectivo_3' => '', // Columna AQ - vacía
            'monto_flujo_3' => '', // Columna AR - vacía
            'codigo_flujo_efectivo_4' => '', // Columna AS - vacía
            'monto_flujo_4' => '', // Columna AT - vacía
            'codigo_flujo_efectivo_5' => '', // Columna AU - vacía
            'monto_flujo_5' => '', // Columna AV - vacía
            'codigo_flujo_efectivo_6' => '', // Columna AW - vacía
            'monto_flujo_6' => '', // Columna AX - vacía
            'codigo_flujo_efectivo_7' => '', // Columna AY - vacía
            'monto_flujo_7' => '', // Columna AZ - vacía
            'codigo_flujo_efectivo_8' => '', // Columna BA - vacía
            'monto_flujo_8' => '', // Columna BB - vacía
            'codigo_flujo_efectivo_9' => '', // Columna BC - vacía
            'monto_flujo_9' => '', // Columna BD - vacía
            'codigo_flujo_efectivo_10' => '', // Columna BE - vacía
            'monto_flujo_10' => '', // Columna BF - vacía
            
            // Información adicional (todas vacías)
            'numero_cuota_pago' => '', // Columna BG - vacía
            'numero_documento_desde' => '', // Columna BH - vacía
            'numero_documento_hasta' => '', // Columna BI - vacía
            
            // Centros de costo concepto presupuesto caja (todas vacías)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna BJ - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BK - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BL - vacía
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna BM - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BN - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BO - vacía
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna BP - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BQ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BR - vacía
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna BS - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BT - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BU - vacía
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna BV - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BW - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BX - vacía
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna BY - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna BZ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna CA - vacía
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna CB - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CC - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CD - vacía
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna CE - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CF - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CG - vacía
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna CH - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CI - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CJ - vacía
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna CK - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CL - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CM - vacía
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
            'debe' => '', // Vacío para HABER
            'haber' => abs($payment->amount), // Siempre usar valor absoluto
            'descripcion_movimiento' => $this->formatCreditDescription($payment, $participant, $program, $paymentOption),
            'equivalencia_moneda' => '', // Columna E - vacía
            'monto_debe_moneda_adicional' => '', // Columna F - vacía
            'monto_haber_moneda_adicional' => '', // Columna G - vacía
            
            // Códigos (columnas H-M vacías)
            'codigo_condicion_venta' => '', // Columna H - vacía
            'codigo_vendedor' => '', // Columna I - vacía
            'codigo_ubicacion' => '', // Columna J - vacía
            'codigo_concepto_caja' => '', // Columna K - vacía
            'codigo_instrumento_financiero' => '', // Columna L - vacía
            'cantidad_instrumento_financiero' => '', // Columna M - vacía
            'codigo_detalle_gasto' => '', // Columna N - vacía
            'cantidad_concepto_gasto' => '', // Columna O - vacía para HABER de boletas
            'codigo_centro_costo' => 'E2-02-01', // Columna P - código para HABER de boletas
            
            // Documentación
            'tipo_docto_conciliacion' => '', // Columna Q - vacía
            'nro_docto_conciliacion' => '', // Columna R - vacía
            'codigo_auxiliar' => $this->formatAuxiliaryCode($participant), // Columna S - RUT sin puntos/guiones
            'tipo_documento' => '', // Columna T - vacío para HABER de boletas
            'nro_documento' => '', // Columna U - vacío para HABER
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna W - formato DD-MM-YYYY
            'tipo_docto_referencia' => '', // Columna X - vacío para HABER
            'nro_docto_referencia' => '', // Columna Y - vacío para HABER
            'nro_correlativo_interno' => '', // Columna Z - vacía
            
            // Montos detalle libro
            'monto_1_detalle_libro' => '', // Columna AA - vacía
            'monto_2_detalle_libro' => '', // Columna AB - vacía para HABER
            'monto_3_detalle_libro' => '', // Columna AC - vacía
            'monto_4_detalle_libro' => '', // Columna AD - vacía
            'monto_5_detalle_libro' => '', // Columna AE - vacía
            'monto_6_detalle_libro' => '', // Columna AF - vacía
            'monto_7_detalle_libro' => '', // Columna AG - vacía
            'monto_8_detalle_libro' => '', // Columna AH - vacía
            'monto_9_detalle_libro' => '', // Columna AI - vacía
            'monto_suma_detalle_libro' => '', // Columna AJ - vacía para HABER
            
            // Configuración
            'graba_detalle_libro' => '', // Columna AK - vacía para HABER
            'documento_nulo' => '', // Columna AL - vacía
            
            // Flujos de efectivo (todas vacías)
            'codigo_flujo_efectivo_1' => '', // Columna AM - vacía
            'monto_flujo_1' => '', // Columna AN - vacía
            'codigo_flujo_efectivo_2' => '', // Columna AO - vacía
            'monto_flujo_2' => '', // Columna AP - vacía
            'codigo_flujo_efectivo_3' => '', // Columna AQ - vacía
            'monto_flujo_3' => '', // Columna AR - vacía
            'codigo_flujo_efectivo_4' => '', // Columna AS - vacía
            'monto_flujo_4' => '', // Columna AT - vacía
            'codigo_flujo_efectivo_5' => '', // Columna AU - vacía
            'monto_flujo_5' => '', // Columna AV - vacía
            'codigo_flujo_efectivo_6' => '', // Columna AW - vacía
            'monto_flujo_6' => '', // Columna AX - vacía
            'codigo_flujo_efectivo_7' => '', // Columna AY - vacía
            'monto_flujo_7' => '', // Columna AZ - vacía
            'codigo_flujo_efectivo_8' => '', // Columna BA - vacía
            'monto_flujo_8' => '', // Columna BB - vacía
            'codigo_flujo_efectivo_9' => '', // Columna BC - vacía
            'monto_flujo_9' => '', // Columna BD - vacía
            'codigo_flujo_efectivo_10' => '', // Columna BE - vacía
            'monto_flujo_10' => '', // Columna BF - vacía
            
            // Información adicional (todas vacías)
            'numero_cuota_pago' => '', // Columna BG - vacía
            'numero_documento_desde' => '', // Columna BH - vacía
            'numero_documento_hasta' => '', // Columna BI - vacía
            
            // Centros de costo concepto presupuesto caja (todas vacías)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna BJ - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BK - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BL - vacía
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna BM - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BN - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BO - vacía
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna BP - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BQ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BR - vacía
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna BS - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BT - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BU - vacía
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna BV - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BW - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BX - vacía
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna BY - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna BZ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna CA - vacía
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna CB - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CC - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CD - vacía
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna CE - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CF - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CG - vacía
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna CH - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CI - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CJ - vacía
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna CK - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CL - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CM - vacía
        ];
    }

    /**
     * Formatea la descripción del movimiento de cobro (DEBE)
     */
    private function formatDescription(Payment $payment, $participant, $paymentOption): string
    {
        $documentType = $payment->document_type ?? 'B2';
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);
        $participantName = $participant ? ($participant->full_name ?? 'N/A') : 'PARTICIPANTE-NO-ENCONTRADO';
        $paymentMethod = $paymentOption ? ($paymentOption->report_code ?? 'SIN-METODO') : 'SIN-METODO';

        return "{$documentType}-{$boletaNumber}-{$participantName}/{$paymentMethod}";
    }

    /**
     * Formatea la descripción del movimiento de ingreso (HABER)
     */
    private function formatCreditDescription(Payment $payment, $participant, $program, $paymentOption): string
    {
        $programCode = $program ? ($program->code ?? 'SIN-CODIGO') : 'SIN-CODIGO';
        $documentType = $payment->document_type ?? 'B2';
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);

        return "N{$programCode}/Programa Educacion/{$documentType}-{$boletaNumber}";
    }

    /**
     * Formatea el código auxiliar (RUT del participante sin puntos ni guiones)
     */
    private function formatAuxiliaryCode($participant): string
    {
        if (!$participant) {
            return '';
        }

        // Obtener el número de documento del participante
        $documentNumber = $participant->document_number ?? '';
        
        // Limpiar puntos y guiones
        $cleanDocument = str_replace(['.', '-'], '', $documentNumber);
        
        return $cleanDocument;
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
        
        return [
            // Información básica
            'codigo_plan_cuenta' => '5-1-01-001', // Cuenta de gastos por servicios
            'debe' => abs($payment->amount), // Usar valor absoluto
            'haber' => '', // Vacío para DEBE
            'descripcion_movimiento' => $this->formatRefundDescription($payment, $participant),
            'equivalencia_moneda' => '', // Columna E - vacía
            'monto_debe_moneda_adicional' => '', // Columna F - vacía
            'monto_haber_moneda_adicional' => '', // Columna G - vacía
            
            // Códigos (columnas H-M vacías)
            'codigo_condicion_venta' => '', // Columna H - vacía
            'codigo_vendedor' => '', // Columna I - vacía
            'codigo_ubicacion' => '', // Columna J - vacía
            'codigo_concepto_caja' => '', // Columna K - vacía
            'codigo_instrumento_financiero' => '', // Columna L - vacía
            'cantidad_instrumento_financiero' => '', // Columna M - vacía
            'codigo_detalle_gasto' => '', // Columna N - vacía
            'cantidad_concepto_gasto' => '', // Columna O - vacía
            'codigo_centro_costo' => '', // Columna P - vacía para DEBE
            
            // Documentación
            'tipo_docto_conciliacion' => '', // Columna Q - vacía
            'nro_docto_conciliacion' => '', // Columna R - vacía
            'codigo_auxiliar' => $this->formatAuxiliaryCode($participant), // Columna S - RUT sin puntos/guiones
            'tipo_documento' => 'NC', // Columna T - Nota de crédito
            'nro_documento' => 'REEMB-' . ($payment->bsale_number ?? ($payment->buy_order ?? $payment->id)), // Columna U - bsale_number
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna W - formato DD-MM-YYYY
            'tipo_docto_referencia' => 'NC', // Columna X - NC para DEBE
            'nro_docto_referencia' => 'REEMB-' . ($payment->bsale_number ?? ($payment->buy_order ?? $payment->id)), // Columna Y - bsale_number
            'nro_correlativo_interno' => '', // Columna Z - vacía
            
            // Montos detalle libro
            'monto_1_detalle_libro' => '', // Columna AA - vacía
            'monto_2_detalle_libro' => abs($payment->amount), // Columna AB - mismo monto del debe
            'monto_3_detalle_libro' => '', // Columna AC - vacía
            'monto_4_detalle_libro' => '', // Columna AD - vacía
            'monto_5_detalle_libro' => '', // Columna AE - vacía
            'monto_6_detalle_libro' => '', // Columna AF - vacía
            'monto_7_detalle_libro' => '', // Columna AG - vacía
            'monto_8_detalle_libro' => '', // Columna AH - vacía
            'monto_9_detalle_libro' => '', // Columna AI - vacía
            'monto_suma_detalle_libro' => abs($payment->amount), // Columna AJ - mismo monto del debe
            
            // Configuración
            'graba_detalle_libro' => 'S', // Columna AK - 'S' para DEBE
            'documento_nulo' => '', // Columna AL - vacía
            
            // Flujos de efectivo (todas vacías)
            'codigo_flujo_efectivo_1' => '', // Columna AM - vacía
            'monto_flujo_1' => '', // Columna AN - vacía
            'codigo_flujo_efectivo_2' => '', // Columna AO - vacía
            'monto_flujo_2' => '', // Columna AP - vacía
            'codigo_flujo_efectivo_3' => '', // Columna AQ - vacía
            'monto_flujo_3' => '', // Columna AR - vacía
            'codigo_flujo_efectivo_4' => '', // Columna AS - vacía
            'monto_flujo_4' => '', // Columna AT - vacía
            'codigo_flujo_efectivo_5' => '', // Columna AU - vacía
            'monto_flujo_5' => '', // Columna AV - vacía
            'codigo_flujo_efectivo_6' => '', // Columna AW - vacía
            'monto_flujo_6' => '', // Columna AX - vacía
            'codigo_flujo_efectivo_7' => '', // Columna AY - vacía
            'monto_flujo_7' => '', // Columna AZ - vacía
            'codigo_flujo_efectivo_8' => '', // Columna BA - vacía
            'monto_flujo_8' => '', // Columna BB - vacía
            'codigo_flujo_efectivo_9' => '', // Columna BC - vacía
            'monto_flujo_9' => '', // Columna BD - vacía
            'codigo_flujo_efectivo_10' => '', // Columna BE - vacía
            'monto_flujo_10' => '', // Columna BF - vacía
            
            // Información adicional (todas vacías)
            'numero_cuota_pago' => '', // Columna BG - vacía
            'numero_documento_desde' => '', // Columna BH - vacía
            'numero_documento_hasta' => '', // Columna BI - vacía
            
            // Centros de costo concepto presupuesto caja (todas vacías)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna BJ - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BK - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BL - vacía
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna BM - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BN - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BO - vacía
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna BP - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BQ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BR - vacía
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna BS - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BT - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BU - vacía
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna BV - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BW - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BX - vacía
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna BY - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna BZ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna CA - vacía
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna CB - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CC - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CD - vacía
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna CE - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CF - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CG - vacía
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna CH - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CI - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CJ - vacía
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna CK - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CL - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CM - vacía
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
            'debe' => '', // Vacío para HABER
            'haber' => abs($payment->amount), // Usar valor absoluto
            'descripcion_movimiento' => $this->formatRefundCreditDescription($payment, $participant, $program),
            'equivalencia_moneda' => '', // Columna E - vacía
            'monto_debe_moneda_adicional' => '', // Columna F - vacía
            'monto_haber_moneda_adicional' => '', // Columna G - vacía
            
            // Códigos (columnas H-M vacías)
            'codigo_condicion_venta' => '', // Columna H - vacía
            'codigo_vendedor' => '', // Columna I - vacía
            'codigo_ubicacion' => '', // Columna J - vacía
            'codigo_concepto_caja' => '', // Columna K - vacía
            'codigo_instrumento_financiero' => '', // Columna L - vacía
            'cantidad_instrumento_financiero' => '', // Columna M - vacía
            'codigo_detalle_gasto' => '', // Columna N - vacía
            'cantidad_concepto_gasto' => '', // Columna O - vacía para HABER
            'codigo_centro_costo' => 'E2-02-01', // Columna P - código para HABER de reembolsos
            
            // Documentación
            'tipo_docto_conciliacion' => '', // Columna Q - vacía
            'nro_docto_conciliacion' => '', // Columna R - vacía
            'codigo_auxiliar' => $this->formatAuxiliaryCode($participant), // Columna S - RUT sin puntos/guiones
            'tipo_documento' => '', // Columna T - vacío para HABER
            'nro_documento' => '', // Columna U - vacío para HABER
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna W - formato DD-MM-YYYY
            'tipo_docto_referencia' => '', // Columna X - vacío para HABER
            'nro_docto_referencia' => '', // Columna Y - vacío para HABER
            'nro_correlativo_interno' => '', // Columna Z - vacía
            
            // Montos detalle libro
            'monto_1_detalle_libro' => '', // Columna AA - vacía
            'monto_2_detalle_libro' => '', // Columna AB - vacía para HABER
            'monto_3_detalle_libro' => '', // Columna AC - vacía
            'monto_4_detalle_libro' => '', // Columna AD - vacía
            'monto_5_detalle_libro' => '', // Columna AE - vacía
            'monto_6_detalle_libro' => '', // Columna AF - vacía
            'monto_7_detalle_libro' => '', // Columna AG - vacía
            'monto_8_detalle_libro' => '', // Columna AH - vacía
            'monto_9_detalle_libro' => '', // Columna AI - vacía
            'monto_suma_detalle_libro' => '', // Columna AJ - vacía para HABER
            
            // Configuración
            'graba_detalle_libro' => '', // Columna AK - vacía para HABER
            'documento_nulo' => '', // Columna AL - vacía
            
            // Flujos de efectivo (todas vacías)
            'codigo_flujo_efectivo_1' => '', // Columna AM - vacía
            'monto_flujo_1' => '', // Columna AN - vacía
            'codigo_flujo_efectivo_2' => '', // Columna AO - vacía
            'monto_flujo_2' => '', // Columna AP - vacía
            'codigo_flujo_efectivo_3' => '', // Columna AQ - vacía
            'monto_flujo_3' => '', // Columna AR - vacía
            'codigo_flujo_efectivo_4' => '', // Columna AS - vacía
            'monto_flujo_4' => '', // Columna AT - vacía
            'codigo_flujo_efectivo_5' => '', // Columna AU - vacía
            'monto_flujo_5' => '', // Columna AV - vacía
            'codigo_flujo_efectivo_6' => '', // Columna AW - vacía
            'monto_flujo_6' => '', // Columna AX - vacía
            'codigo_flujo_efectivo_7' => '', // Columna AY - vacía
            'monto_flujo_7' => '', // Columna AZ - vacía
            'codigo_flujo_efectivo_8' => '', // Columna BA - vacía
            'monto_flujo_8' => '', // Columna BB - vacía
            'codigo_flujo_efectivo_9' => '', // Columna BC - vacía
            'monto_flujo_9' => '', // Columna BD - vacía
            'codigo_flujo_efectivo_10' => '', // Columna BE - vacía
            'monto_flujo_10' => '', // Columna BF - vacía
            
            // Información adicional (todas vacías)
            'numero_cuota_pago' => '', // Columna BG - vacía
            'numero_documento_desde' => '', // Columna BH - vacía
            'numero_documento_hasta' => '', // Columna BI - vacía
            
            // Centros de costo concepto presupuesto caja (todas vacías)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna BJ - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BK - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna BL - vacía
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna BM - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BN - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna BO - vacía
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna BP - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BQ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna BR - vacía
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna BS - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BT - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna BU - vacía
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna BV - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BW - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna BX - vacía
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna BY - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna BZ - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna CA - vacía
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna CB - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CC - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna CD - vacía
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna CE - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CF - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna CG - vacía
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna CH - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CI - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna CJ - vacía
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna CK - vacía
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CL - vacía
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna CM - vacía
        ];
    }

    /**
     * Formatea la descripción del reembolso (DEBE)
     */
    private function formatRefundDescription(Payment $payment, $participant): string
    {
        $documentType = 'NC'; // Nota de crédito para reembolsos
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);
        $participantName = $participant ? ($participant->full_name ?? 'N/A') : 'PARTICIPANTE-NO-ENCONTRADO';
        $paymentMethod = 'REEMBOLSO';

        return "{$documentType}-{$boletaNumber}-{$participantName}/{$paymentMethod}";
    }

    /**
     * Formatea la descripción del pasivo por reembolso (HABER)
     */
    private function formatRefundCreditDescription(Payment $payment, $participant, $program): string
    {
        $programCode = $program ? ($program->code ?? 'SIN-CODIGO') : 'SIN-CODIGO';
        $documentType = 'NC'; // Nota de crédito para reembolsos
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);

        return "N{$programCode}/Programa Educacion/{$documentType}-{$boletaNumber}";
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

    /**
     * Formatea fecha en formato DD-MM-YYYY
     */
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
