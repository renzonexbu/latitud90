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
        // Obtener SOLO pagos completados que generaron boleta (B2) o anticipo (AC) y NO son presenciales
        $payments = Payment::with(['paymentOption', 'order.participant.emergencyContacts', 'order.program', 'order.participantProgram', 'order.orderDetails'])
            ->where('status', 'completed')
            ->whereIn('document_type', ['B2', 'AC'])
            ->whereHas('paymentOption', function ($query) {
                $query->where('mode', '!=', 'presential');
            })
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
                // Para pagos normales: separar DEBE y HABER según tipo de documento
                if ($payment->document_type === 'AC') {
                    $debitMovements->push($this->createACDebitMovement($payment));
                    $creditMovements->push($this->createACCreditMovement($payment));
                } else {
                    // B2 o cualquier otro tipo
                    $debitMovements->push($this->createDebitMovement($payment));
                    $creditMovements->push($this->createCreditMovement($payment));
                }
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
            'debe' => (int) abs($payment->amount), // Siempre usar valor absoluto sin decimales
            'haber' => 0, // Vacío para DEBE
            'descripcion_movimiento' => $this->formatDescription($payment, $participant, $paymentOption),
            'equivalencia_moneda' => '', // Columna 5 - Equivalencia Moneda
            'monto_debe_moneda_adicional' => '', // Columna 6 - Monto al Debe Moneda Adicional
            'monto_haber_moneda_adicional' => '', // Columna 7 - Monto al Haber Moneda Adicional

            // Códigos (columnas 8-16)
            'codigo_condicion_venta' => '', // Columna 8 - Código Condición de Venta
            'codigo_vendedor' => '', // Columna 9 - Código Vendedor
            'codigo_ubicacion' => '', // Columna 10 - Código Ubicación
            'codigo_concepto_caja' => '', // Columna 11 - Código Concepto de Caja
            'codigo_instrumento_financiero' => '', // Columna 12 - Código Instrumento Financiero
            'cantidad_instrumento_financiero' => '', // Columna 13 - Cantidad Instrumento Financiero
            'codigo_detalle_gasto' => '', // Columna 14 - Código Detalle de Gasto
            'cantidad_concepto_gasto' => '', // Columna 15 - Cantidad Concepto de Gasto
            'codigo_centro_costo' => '', // Columna 16 - Código Centro de Costo (vacía para DEBE)

            // Documentación (columnas 17-26)
            'tipo_docto_conciliacion' => '', // Columna 17 - Tipo Docto. Conciliación
            'nro_docto_conciliacion' => '', // Columna 18 - Nro. Docto. Conciliación
            'codigo_auxiliar' => $this->formatAuxiliaryCode($payment), // Columna 19 - Código Auxiliar
            'tipo_documento' => $payment->document_type ?? 'B2', // Columna 20 - Tipo Documento
            'nro_documento' => $payment->bsale_number ?? ($payment->buy_order ?? $payment->id), // Columna 21 - Nro. Documento
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna 22 - Fecha Emisión Docto.
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna 23 - Fecha Vencimiento Docto.
            'tipo_docto_referencia' => $payment->document_type ?? 'B2', // Columna 24 - Tipo Docto. Referencia
            'nro_docto_referencia' => $payment->bsale_number ?? ($payment->buy_order ?? $payment->id), // Columna 25 - Nro. Docto. Referencia
            'nro_correlativo_interno' => '', // Columna 26 - Nro. Correlativo Interno

            // Montos detalle libro (columnas 27-36)
            'monto_1_detalle_libro' => '', // Columna 27 - Monto 1 Detalle Libro
            'monto_2_detalle_libro' => (int) abs($payment->amount), // Columna 28 - Monto 2 Detalle Libro (sin decimales)
            'monto_3_detalle_libro' => '', // Columna 29 - Monto 3 Detalle Libro
            'monto_4_detalle_libro' => '', // Columna 30 - Monto 4 Detalle Libro
            'monto_5_detalle_libro' => '', // Columna 31 - Monto 5 Detalle Libro
            'monto_6_detalle_libro' => '', // Columna 32 - Monto 6 Detalle Libro
            'monto_7_detalle_libro' => '', // Columna 33 - Monto 7 Detalle Libro
            'monto_8_detalle_libro' => '', // Columna 34 - Monto 8 Detalle Libro
            'monto_9_detalle_libro' => '', // Columna 35 - Monto 9 Detalle Libro
            'monto_suma_detalle_libro' => (int) abs($payment->amount), // Columna 36 - Monto Suma Detalle Libro (sin decimales)

            // Configuración (columnas 37-38)
            'graba_detalle_libro' => 'S', // Columna 37 - Graba el detalle de libro (S/N)
            'documento_nulo' => '', // Columna 38 - Documento Nulo (S/N)

            // Flujos de efectivo (columnas 39-58)
            'codigo_flujo_efectivo_1' => '', // Columna 39 - Código flujo efectivo 1
            'monto_flujo_1' => '', // Columna 40 - Monto flujo 1
            'codigo_flujo_efectivo_2' => '', // Columna 41 - Código flujo efectivo 2
            'monto_flujo_2' => '', // Columna 42 - Monto flujo 2
            'codigo_flujo_efectivo_3' => '', // Columna 43 - Código flujo efectivo 3
            'monto_flujo_3' => '', // Columna 44 - Monto flujo 3
            'codigo_flujo_efectivo_4' => '', // Columna 45 - Código flujo efectivo 4
            'monto_flujo_4' => '', // Columna 46 - Monto flujo 4
            'codigo_flujo_efectivo_5' => '', // Columna 47 - Código flujo efectivo 5
            'monto_flujo_5' => '', // Columna 48 - Monto flujo 5
            'codigo_flujo_efectivo_6' => '', // Columna 49 - Código flujo efectivo 6
            'monto_flujo_6' => '', // Columna 50 - Monto flujo 6
            'codigo_flujo_efectivo_7' => '', // Columna 51 - Código flujo efectivo 7
            'monto_flujo_7' => '', // Columna 52 - Monto flujo 7
            'codigo_flujo_efectivo_8' => '', // Columna 53 - Código flujo efectivo 8
            'monto_flujo_8' => '', // Columna 54 - Monto flujo 8
            'codigo_flujo_efectivo_9' => '', // Columna 55 - Código flujo efectivo 9
            'monto_flujo_9' => '', // Columna 56 - Monto flujo 9
            'codigo_flujo_efectivo_10' => '', // Columna 57 - Código flujo efectivo 10
            'monto_flujo_10' => '', // Columna 58 - Monto flujo 10

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59 - Número Cuota de Pago
            'numero_documento_desde' => '', // Columna 60 - Número Documento Desde
            'numero_documento_hasta' => '', // Columna 61 - Número Documento Hasta

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62 - Centro Costo Concepto Presupuesto Caja 1
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63 - Monto Moneda Base Centro Costo 1
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64 - Monto Moneda Adicional Centro Costo 1
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65 - Centro Costo Concepto Presupuesto Caja 2
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66 - Monto Moneda Base Centro Costo 2
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67 - Monto Moneda Adicional Centro Costo 2
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68 - Centro Costo Concepto Presupuesto Caja 3
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69 - Monto Moneda Base Centro Costo 3
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70 - Monto Moneda Adicional Centro Costo 3
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71 - Centro Costo Concepto Presupuesto Caja 4
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72 - Monto Moneda Base Centro Costo 4
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73 - Monto Moneda Adicional Centro Costo 4
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74 - Centro Costo Concepto Presupuesto Caja 5
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75 - Monto Moneda Base Centro Costo 5
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76 - Monto Moneda Adicional Centro Costo 5
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77 - Centro Costo Concepto Presupuesto Caja 6
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78 - Monto Moneda Base Centro Costo 6
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79 - Monto Moneda Adicional Centro Costo 6
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80 - Centro Costo Concepto Presupuesto Caja 7
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81 - Monto Moneda Base Centro Costo 7
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82 - Monto Moneda Adicional Centro Costo 7
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83 - Centro Costo Concepto Presupuesto Caja 8
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84 - Monto Moneda Base Centro Costo 8
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85 - Monto Moneda Adicional Centro Costo 8
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86 - Centro Costo Concepto Presupuesto Caja 9
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87 - Monto Moneda Base Centro Costo 9
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88 - Monto Moneda Adicional Centro Costo 9
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89 - Centro Costo Concepto Presupuesto Caja 10
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90 - Monto Moneda Base Centro Costo 10
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91 - Monto Moneda Adicional Centro Costo 10
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
            'debe' => 0, // Vacío para HABER
            'haber' => (int) abs($payment->amount), // Siempre usar valor absoluto sin decimales
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
            'codigo_auxiliar' => '', // Columna S - vacío para HABER de boletas
            'tipo_documento' => '', // Columna T - vacío para HABER de boletas
            'nro_documento' => '', // Columna U - vacío para HABER
            'fecha_emision_docto' => '', // Columna V - vacío para HABER de boletas
            'fecha_vencimiento_docto' => '', // Columna W - vacío para HABER de boletas
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
            'codigo_flujo_efectivo_3' => '', // Columna 43 - Código flujo efectivo 3
            'monto_flujo_3' => '', // Columna 44 - Monto flujo 3
            'codigo_flujo_efectivo_4' => '', // Columna 45 - Código flujo efectivo 4
            'monto_flujo_4' => '', // Columna 46 - Monto flujo 4
            'codigo_flujo_efectivo_5' => '', // Columna 47 - Código flujo efectivo 5
            'monto_flujo_5' => '', // Columna 48 - Monto flujo 5
            'codigo_flujo_efectivo_6' => '', // Columna 49 - Código flujo efectivo 6
            'monto_flujo_6' => '', // Columna 50 - Monto flujo 6
            'codigo_flujo_efectivo_7' => '', // Columna 51 - Código flujo efectivo 7
            'monto_flujo_7' => '', // Columna 52 - Monto flujo 7
            'codigo_flujo_efectivo_8' => '', // Columna 53 - Código flujo efectivo 8
            'monto_flujo_8' => '', // Columna 54 - Monto flujo 8
            'codigo_flujo_efectivo_9' => '', // Columna 55 - Código flujo efectivo 9
            'monto_flujo_9' => '', // Columna 56 - Monto flujo 9
            'codigo_flujo_efectivo_10' => '', // Columna 57 - Código flujo efectivo 10
            'monto_flujo_10' => '', // Columna 58 - Monto flujo 10

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59 - Número Cuota de Pago
            'numero_documento_desde' => '', // Columna 60 - Número Documento Desde
            'numero_documento_hasta' => '', // Columna 61 - Número Documento Hasta

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62 - Centro Costo Concepto Presupuesto Caja 1
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63 - Monto Moneda Base Centro Costo 1
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64 - Monto Moneda Adicional Centro Costo 1
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65 - Centro Costo Concepto Presupuesto Caja 2
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66 - Monto Moneda Base Centro Costo 2
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67 - Monto Moneda Adicional Centro Costo 2
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68 - Centro Costo Concepto Presupuesto Caja 3
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69 - Monto Moneda Base Centro Costo 3
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70 - Monto Moneda Adicional Centro Costo 3
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71 - Centro Costo Concepto Presupuesto Caja 4
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72 - Monto Moneda Base Centro Costo 4
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73 - Monto Moneda Adicional Centro Costo 4
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74 - Centro Costo Concepto Presupuesto Caja 5
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75 - Monto Moneda Base Centro Costo 5
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76 - Monto Moneda Adicional Centro Costo 5
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77 - Centro Costo Concepto Presupuesto Caja 6
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78 - Monto Moneda Base Centro Costo 6
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79 - Monto Moneda Adicional Centro Costo 6
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80 - Centro Costo Concepto Presupuesto Caja 7
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81 - Monto Moneda Base Centro Costo 7
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82 - Monto Moneda Adicional Centro Costo 7
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83 - Centro Costo Concepto Presupuesto Caja 8
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84 - Monto Moneda Base Centro Costo 8
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85 - Monto Moneda Adicional Centro Costo 8
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86 - Centro Costo Concepto Presupuesto Caja 9
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87 - Monto Moneda Base Centro Costo 9
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88 - Monto Moneda Adicional Centro Costo 9
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89 - Centro Costo Concepto Presupuesto Caja 10
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90 - Monto Moneda Base Centro Costo 10
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91 - Monto Moneda Adicional Centro Costo 10
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

        return "N{$programCode}/Programa educación/{$documentType}-{$boletaNumber}";
    }

    /**
     * Formatea el código auxiliar (RUT del comprador de order_details sin puntos ni guiones, siempre en mayúsculas)
     */
    private function formatAuxiliaryCode($payment): string
    {
        if (!$payment || !$payment->order || !$payment->order->orderDetails) {
            return '';
        }

        // Obtener el primer order_detail para obtener el documento del comprador
        $orderDetail = $payment->order->orderDetails->first();
        if (!$orderDetail) {
            return '';
        }

        // Obtener el número de documento del comprador
        $documentNumber = $orderDetail->document_number ?? '';

        // Limpiar puntos y guiones y convertir a mayúsculas
        $cleanDocument = str_replace(['.', '-'], '', $documentNumber);

        return strtoupper($cleanDocument);
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
            'debe' => (int) abs($payment->amount), // Usar valor absoluto sin decimales
            'haber' => 0, // Vacío para DEBE
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
            'codigo_auxiliar' => $this->formatAuxiliaryCode($payment), // Columna S - RUT del comprador sin puntos/guiones
            'tipo_documento' => 'NC', // Columna T - Nota de crédito
            'nro_documento' => 'REEMB-' . ($payment->bsale_number ?? ($payment->buy_order ?? $payment->id)), // Columna U - bsale_number
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna W - formato DD-MM-YYYY
            'tipo_docto_referencia' => 'NC', // Columna X - NC para DEBE
            'nro_docto_referencia' => 'REEMB-' . ($payment->bsale_number ?? ($payment->buy_order ?? $payment->id)), // Columna Y - bsale_number
            'nro_correlativo_interno' => '', // Columna Z - vacía

            // Montos detalle libro
            'monto_1_detalle_libro' => '', // Columna AA - vacía
            'monto_2_detalle_libro' => '', // Columna AB - vacía para AC
            'monto_3_detalle_libro' => '', // Columna AC - vacía
            'monto_4_detalle_libro' => '', // Columna AD - vacía
            'monto_5_detalle_libro' => '', // Columna AE - vacía
            'monto_6_detalle_libro' => '', // Columna AF - vacía
            'monto_7_detalle_libro' => '', // Columna AG - vacía
            'monto_8_detalle_libro' => '', // Columna AH - vacía
            'monto_9_detalle_libro' => '', // Columna AI - vacía
            'monto_suma_detalle_libro' => '', // Columna AJ - vacía para AC

            // Configuración
            'graba_detalle_libro' => '', // Columna AK - vacía para AC
            'documento_nulo' => '', // Columna AL - vacía

            // Flujos de efectivo (todas vacías)
            'codigo_flujo_efectivo_1' => '', // Columna AM - vacía
            'monto_flujo_1' => '', // Columna AN - vacía
            'codigo_flujo_efectivo_2' => '', // Columna AO - vacía
            'monto_flujo_2' => '', // Columna AP - vacía
            'codigo_flujo_efectivo_3' => '', // Columna 43 - Código flujo efectivo 3
            'monto_flujo_3' => '', // Columna 44 - Monto flujo 3
            'codigo_flujo_efectivo_4' => '', // Columna 45 - Código flujo efectivo 4
            'monto_flujo_4' => '', // Columna 46 - Monto flujo 4
            'codigo_flujo_efectivo_5' => '', // Columna 47 - Código flujo efectivo 5
            'monto_flujo_5' => '', // Columna 48 - Monto flujo 5
            'codigo_flujo_efectivo_6' => '', // Columna 49 - Código flujo efectivo 6
            'monto_flujo_6' => '', // Columna 50 - Monto flujo 6
            'codigo_flujo_efectivo_7' => '', // Columna 51 - Código flujo efectivo 7
            'monto_flujo_7' => '', // Columna 52 - Monto flujo 7
            'codigo_flujo_efectivo_8' => '', // Columna 53 - Código flujo efectivo 8
            'monto_flujo_8' => '', // Columna 54 - Monto flujo 8
            'codigo_flujo_efectivo_9' => '', // Columna 55 - Código flujo efectivo 9
            'monto_flujo_9' => '', // Columna 56 - Monto flujo 9
            'codigo_flujo_efectivo_10' => '', // Columna 57 - Código flujo efectivo 10
            'monto_flujo_10' => '', // Columna 58 - Monto flujo 10

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59 - Número Cuota de Pago
            'numero_documento_desde' => '', // Columna 60 - Número Documento Desde
            'numero_documento_hasta' => '', // Columna 61 - Número Documento Hasta

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62 - Centro Costo Concepto Presupuesto Caja 1
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63 - Monto Moneda Base Centro Costo 1
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64 - Monto Moneda Adicional Centro Costo 1
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65 - Centro Costo Concepto Presupuesto Caja 2
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66 - Monto Moneda Base Centro Costo 2
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67 - Monto Moneda Adicional Centro Costo 2
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68 - Centro Costo Concepto Presupuesto Caja 3
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69 - Monto Moneda Base Centro Costo 3
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70 - Monto Moneda Adicional Centro Costo 3
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71 - Centro Costo Concepto Presupuesto Caja 4
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72 - Monto Moneda Base Centro Costo 4
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73 - Monto Moneda Adicional Centro Costo 4
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74 - Centro Costo Concepto Presupuesto Caja 5
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75 - Monto Moneda Base Centro Costo 5
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76 - Monto Moneda Adicional Centro Costo 5
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77 - Centro Costo Concepto Presupuesto Caja 6
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78 - Monto Moneda Base Centro Costo 6
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79 - Monto Moneda Adicional Centro Costo 6
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80 - Centro Costo Concepto Presupuesto Caja 7
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81 - Monto Moneda Base Centro Costo 7
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82 - Monto Moneda Adicional Centro Costo 7
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83 - Centro Costo Concepto Presupuesto Caja 8
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84 - Monto Moneda Base Centro Costo 8
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85 - Monto Moneda Adicional Centro Costo 8
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86 - Centro Costo Concepto Presupuesto Caja 9
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87 - Monto Moneda Base Centro Costo 9
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88 - Monto Moneda Adicional Centro Costo 9
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89 - Centro Costo Concepto Presupuesto Caja 10
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90 - Monto Moneda Base Centro Costo 10
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91 - Monto Moneda Adicional Centro Costo 10
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
            'debe' => 0, // Vacío para HABER
            'haber' => (int) abs($payment->amount), // Usar valor absoluto sin decimales
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
            'codigo_auxiliar' => $this->formatAuxiliaryCode($payment), // Columna S - RUT del comprador sin puntos/guiones
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
            'codigo_flujo_efectivo_3' => '', // Columna 43 - Código flujo efectivo 3
            'monto_flujo_3' => '', // Columna 44 - Monto flujo 3
            'codigo_flujo_efectivo_4' => '', // Columna 45 - Código flujo efectivo 4
            'monto_flujo_4' => '', // Columna 46 - Monto flujo 4
            'codigo_flujo_efectivo_5' => '', // Columna 47 - Código flujo efectivo 5
            'monto_flujo_5' => '', // Columna 48 - Monto flujo 5
            'codigo_flujo_efectivo_6' => '', // Columna 49 - Código flujo efectivo 6
            'monto_flujo_6' => '', // Columna 50 - Monto flujo 6
            'codigo_flujo_efectivo_7' => '', // Columna 51 - Código flujo efectivo 7
            'monto_flujo_7' => '', // Columna 52 - Monto flujo 7
            'codigo_flujo_efectivo_8' => '', // Columna 53 - Código flujo efectivo 8
            'monto_flujo_8' => '', // Columna 54 - Monto flujo 8
            'codigo_flujo_efectivo_9' => '', // Columna 55 - Código flujo efectivo 9
            'monto_flujo_9' => '', // Columna 56 - Monto flujo 9
            'codigo_flujo_efectivo_10' => '', // Columna 57 - Código flujo efectivo 10
            'monto_flujo_10' => '', // Columna 58 - Monto flujo 10

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59 - Número Cuota de Pago
            'numero_documento_desde' => '', // Columna 60 - Número Documento Desde
            'numero_documento_hasta' => '', // Columna 61 - Número Documento Hasta

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62 - Centro Costo Concepto Presupuesto Caja 1
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63 - Monto Moneda Base Centro Costo 1
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64 - Monto Moneda Adicional Centro Costo 1
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65 - Centro Costo Concepto Presupuesto Caja 2
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66 - Monto Moneda Base Centro Costo 2
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67 - Monto Moneda Adicional Centro Costo 2
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68 - Centro Costo Concepto Presupuesto Caja 3
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69 - Monto Moneda Base Centro Costo 3
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70 - Monto Moneda Adicional Centro Costo 3
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71 - Centro Costo Concepto Presupuesto Caja 4
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72 - Monto Moneda Base Centro Costo 4
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73 - Monto Moneda Adicional Centro Costo 4
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74 - Centro Costo Concepto Presupuesto Caja 5
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75 - Monto Moneda Base Centro Costo 5
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76 - Monto Moneda Adicional Centro Costo 5
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77 - Centro Costo Concepto Presupuesto Caja 6
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78 - Monto Moneda Base Centro Costo 6
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79 - Monto Moneda Adicional Centro Costo 6
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80 - Centro Costo Concepto Presupuesto Caja 7
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81 - Monto Moneda Base Centro Costo 7
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82 - Monto Moneda Adicional Centro Costo 7
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83 - Centro Costo Concepto Presupuesto Caja 8
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84 - Monto Moneda Base Centro Costo 8
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85 - Monto Moneda Adicional Centro Costo 8
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86 - Centro Costo Concepto Presupuesto Caja 9
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87 - Monto Moneda Base Centro Costo 9
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88 - Monto Moneda Adicional Centro Costo 9
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89 - Centro Costo Concepto Presupuesto Caja 10
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90 - Monto Moneda Base Centro Costo 10
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91 - Monto Moneda Adicional Centro Costo 10
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
        $programName = $program ? ($program->name ?? 'SIN-NOMBRE') : 'SIN-NOMBRE';
        $documentType = 'NC'; // Nota de crédito para reembolsos
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);

        return "N{$programCode}/{$programName}/{$documentType}-{$boletaNumber}";
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

    /**
     * Crea el movimiento de cobro para AC (DEBE)
     */
    private function createACDebitMovement(Payment $payment): array
    {
        $participant = $payment->order->participant;
        $paymentOption = $payment->paymentOption;

        // Obtener el nombre del contacto de emergencia
        $emergencyContactName = '';
        if ($participant && $participant->emergencyContacts()->exists()) {
            $emergencyContact = $participant->emergencyContacts()->first();
            $emergencyContactName = ucwords(strtolower($emergencyContact->name ?? ''));
        }

        return [
            // Información básica
            'codigo_plan_cuenta' => '1-1-02-014', // Cuenta específica para AC
            'debe' => (int) abs($payment->amount), // Mismo monto que haber sin decimales
            'haber' => 0, // Vacío para DEBE
            'descripcion_movimiento' => $emergencyContactName, // Solo el nombre del contacto de emergencia
            'equivalencia_moneda' => '', // Columna 5 - vacía
            'monto_debe_moneda_adicional' => '', // Columna 6 - vacía
            'monto_haber_moneda_adicional' => '', // Columna 7 - vacía

            // Códigos (columnas 8-16 vacías)
            'codigo_condicion_venta' => '', // Columna 8 - vacía
            'codigo_vendedor' => '', // Columna 9 - vacía
            'codigo_ubicacion' => '', // Columna 10 - vacía
            'codigo_concepto_caja' => '', // Columna 11 - vacía
            'codigo_instrumento_financiero' => '', // Columna 12 - vacía
            'cantidad_instrumento_financiero' => '', // Columna 13 - vacía
            'codigo_detalle_gasto' => '', // Columna 14 - vacía
            'cantidad_concepto_gasto' => '', // Columna 15 - vacía
            'codigo_centro_costo' => '', // Columna 16 - vacía para AC

            // Documentación (columnas 17-26)
            'tipo_docto_conciliacion' => '', // Columna 17 - vacía
            'nro_docto_conciliacion' => '', // Columna 18 - vacía
            'codigo_auxiliar' => $this->formatAuxiliaryCode($payment), // Columna 19 - mismo que haber
            'tipo_documento' => $paymentOption->report_code ?? '', // Columna 20 - payment_option.report_code
            'nro_documento' => $this->getDocumentNumber($payment), // Columna 21 - mismo que haber
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date),
            'tipo_docto_referencia' => $paymentOption->report_code ?? '', // Columna 24 - payment_option.report_code
            'nro_docto_referencia' => $this->getDocumentNumber($payment), // Columna 25 - vacía
            'fecha_docto_referencia' => '', // Columna 26 - vacía

            // Montos detalle libro (columnas 27-36 vacías)
            'monto_1_detalle_libro' => '', // Columna 27 - vacía
            'monto_2_detalle_libro' => '', // Columna 28 - vacía
            'monto_3_detalle_libro' => '', // Columna 29 - vacía
            'monto_4_detalle_libro' => '', // Columna 30 - vacía
            'monto_5_detalle_libro' => '', // Columna 31 - vacía
            'monto_6_detalle_libro' => '', // Columna 32 - vacía
            'monto_7_detalle_libro' => '', // Columna 33 - vacía
            'monto_8_detalle_libro' => '', // Columna 34 - vacía
            'monto_9_detalle_libro' => '', // Columna 35 - vacía
            'monto_suma_detalle_libro' => '', // Columna 36 - vacía

            // Configuración (columnas 37-38 vacías)
            'graba_detalle_libro' => '', // Columna 37 - vacía
            'codigo_moneda_adicional' => '', // Columna 38 - vacía

            // Flujos de efectivo (todas vacías)
            'codigo_flujo_efectivo_1' => '', // Columna AM - vacía
            'monto_flujo_1' => '', // Columna AN - vacía
            'codigo_flujo_efectivo_2' => '', // Columna AO - vacía
            'monto_flujo_2' => '', // Columna AP - vacía
            'codigo_flujo_efectivo_3' => '', // Columna 43 - Código flujo efectivo 3
            'monto_flujo_3' => '', // Columna 44 - Monto flujo 3
            'codigo_flujo_efectivo_4' => '', // Columna 45 - Código flujo efectivo 4
            'monto_flujo_4' => '', // Columna 46 - Monto flujo 4
            'codigo_flujo_efectivo_5' => '', // Columna 47 - Código flujo efectivo 5
            'monto_flujo_5' => '', // Columna 48 - Monto flujo 5
            'codigo_flujo_efectivo_6' => '', // Columna 49 - Código flujo efectivo 6
            'monto_flujo_6' => '', // Columna 50 - Monto flujo 6
            'codigo_flujo_efectivo_7' => '', // Columna 51 - Código flujo efectivo 7
            'monto_flujo_7' => '', // Columna 52 - Monto flujo 7
            'codigo_flujo_efectivo_8' => '', // Columna 53 - Código flujo efectivo 8
            'monto_flujo_8' => '', // Columna 54 - Monto flujo 8
            'codigo_flujo_efectivo_9' => '', // Columna 55 - Código flujo efectivo 9
            'monto_flujo_9' => '', // Columna 56 - Monto flujo 9
            'codigo_flujo_efectivo_10' => '', // Columna 57 - Código flujo efectivo 10
            'monto_flujo_10' => '', // Columna 58 - Monto flujo 10

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59 - Número Cuota de Pago
            'numero_documento_desde' => '', // Columna 60 - Número Documento Desde
            'numero_documento_hasta' => '', // Columna 61 - Número Documento Hasta

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62 - Centro Costo Concepto Presupuesto Caja 1
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63 - Monto Moneda Base Centro Costo 1
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64 - Monto Moneda Adicional Centro Costo 1
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65 - Centro Costo Concepto Presupuesto Caja 2
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66 - Monto Moneda Base Centro Costo 2
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67 - Monto Moneda Adicional Centro Costo 2
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68 - Centro Costo Concepto Presupuesto Caja 3
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69 - Monto Moneda Base Centro Costo 3
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70 - Monto Moneda Adicional Centro Costo 3
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71 - Centro Costo Concepto Presupuesto Caja 4
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72 - Monto Moneda Base Centro Costo 4
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73 - Monto Moneda Adicional Centro Costo 4
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74 - Centro Costo Concepto Presupuesto Caja 5
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75 - Monto Moneda Base Centro Costo 5
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76 - Monto Moneda Adicional Centro Costo 5
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77 - Centro Costo Concepto Presupuesto Caja 6
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78 - Monto Moneda Base Centro Costo 6
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79 - Monto Moneda Adicional Centro Costo 6
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80 - Centro Costo Concepto Presupuesto Caja 7
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81 - Monto Moneda Base Centro Costo 7
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82 - Monto Moneda Adicional Centro Costo 7
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83 - Centro Costo Concepto Presupuesto Caja 8
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84 - Monto Moneda Base Centro Costo 8
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85 - Monto Moneda Adicional Centro Costo 8
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86 - Centro Costo Concepto Presupuesto Caja 9
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87 - Monto Moneda Base Centro Costo 9
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88 - Monto Moneda Adicional Centro Costo 9
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89 - Centro Costo Concepto Presupuesto Caja 10
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90 - Monto Moneda Base Centro Costo 10
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91 - Monto Moneda Adicional Centro Costo 10
        ];
    }

    /**
     * Crea el movimiento de anticipo (HABER) para AC
     */
    private function createACCreditMovement(Payment $payment): array
    {
        $participant = $payment->order->participant;
        $program = $payment->order->program;
        $paymentOption = $payment->paymentOption;

        return [
            // Información básica
            'codigo_plan_cuenta' => '2-1-04-060', // Cuenta de anticipos según especificación
            'debe' => 0, // Vacío para HABER
            'haber' => (int) abs($payment->amount), // Siempre usar valor absoluto sin decimales
            'descripcion_movimiento' => $this->formatACCreditDescription($payment, $participant, $program),
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
            'codigo_centro_costo' => '', // Columna P - vacío para AC HABER

            // Documentación
            'tipo_docto_conciliacion' => '', // Columna 17 - vacía
            'nro_docto_conciliacion' => '', // Columna 18 - vacía
            'codigo_auxiliar' => $this->formatAuxiliaryCode($payment), // Columna 19 - mismo que haber
            'tipo_documento' => 'AC', // Columna 20 - payment_option.report_code
            'nro_documento' => $this->getDocumentNumber($payment), // Columna 21 - mismo que haber
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->transaction_date),
            'tipo_docto_referencia' => 'AC', // Columna 24 - payment_option.report_code
            'nro_docto_referencia' => $this->getDocumentNumber($payment), // Columna 25 - vacía
            'fecha_docto_referencia' => '', // Columna 26 - vacía

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
            'codigo_flujo_efectivo_3' => '', // Columna 43 - Código flujo efectivo 3
            'monto_flujo_3' => '', // Columna 44 - Monto flujo 3
            'codigo_flujo_efectivo_4' => '', // Columna 45 - Código flujo efectivo 4
            'monto_flujo_4' => '', // Columna 46 - Monto flujo 4
            'codigo_flujo_efectivo_5' => '', // Columna 47 - Código flujo efectivo 5
            'monto_flujo_5' => '', // Columna 48 - Monto flujo 5
            'codigo_flujo_efectivo_6' => '', // Columna 49 - Código flujo efectivo 6
            'monto_flujo_6' => '', // Columna 50 - Monto flujo 6
            'codigo_flujo_efectivo_7' => '', // Columna 51 - Código flujo efectivo 7
            'monto_flujo_7' => '', // Columna 52 - Monto flujo 7
            'codigo_flujo_efectivo_8' => '', // Columna 53 - Código flujo efectivo 8
            'monto_flujo_8' => '', // Columna 54 - Monto flujo 8
            'codigo_flujo_efectivo_9' => '', // Columna 55 - Código flujo efectivo 9
            'monto_flujo_9' => '', // Columna 56 - Monto flujo 9
            'codigo_flujo_efectivo_10' => '', // Columna 57 - Código flujo efectivo 10
            'monto_flujo_10' => '', // Columna 58 - Monto flujo 10

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '', // Columna 59 - Número Cuota de Pago
            'numero_documento_desde' => '', // Columna 60 - Número Documento Desde
            'numero_documento_hasta' => '', // Columna 61 - Número Documento Hasta

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '', // Columna 62 - Centro Costo Concepto Presupuesto Caja 1
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 63 - Monto Moneda Base Centro Costo 1
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '', // Columna 64 - Monto Moneda Adicional Centro Costo 1
            'centro_costo_concepto_presupuesto_caja_2' => '', // Columna 65 - Centro Costo Concepto Presupuesto Caja 2
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 66 - Monto Moneda Base Centro Costo 2
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '', // Columna 67 - Monto Moneda Adicional Centro Costo 2
            'centro_costo_concepto_presupuesto_caja_3' => '', // Columna 68 - Centro Costo Concepto Presupuesto Caja 3
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 69 - Monto Moneda Base Centro Costo 3
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '', // Columna 70 - Monto Moneda Adicional Centro Costo 3
            'centro_costo_concepto_presupuesto_caja_4' => '', // Columna 71 - Centro Costo Concepto Presupuesto Caja 4
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 72 - Monto Moneda Base Centro Costo 4
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '', // Columna 73 - Monto Moneda Adicional Centro Costo 4
            'centro_costo_concepto_presupuesto_caja_5' => '', // Columna 74 - Centro Costo Concepto Presupuesto Caja 5
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 75 - Monto Moneda Base Centro Costo 5
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '', // Columna 76 - Monto Moneda Adicional Centro Costo 5
            'centro_costo_concepto_presupuesto_caja_6' => '', // Columna 77 - Centro Costo Concepto Presupuesto Caja 6
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 78 - Monto Moneda Base Centro Costo 6
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '', // Columna 79 - Monto Moneda Adicional Centro Costo 6
            'centro_costo_concepto_presupuesto_caja_7' => '', // Columna 80 - Centro Costo Concepto Presupuesto Caja 7
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 81 - Monto Moneda Base Centro Costo 7
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '', // Columna 82 - Monto Moneda Adicional Centro Costo 7
            'centro_costo_concepto_presupuesto_caja_8' => '', // Columna 83 - Centro Costo Concepto Presupuesto Caja 8
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 84 - Monto Moneda Base Centro Costo 8
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '', // Columna 85 - Monto Moneda Adicional Centro Costo 8
            'centro_costo_concepto_presupuesto_caja_9' => '', // Columna 86 - Centro Costo Concepto Presupuesto Caja 9
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 87 - Monto Moneda Base Centro Costo 9
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '', // Columna 88 - Monto Moneda Adicional Centro Costo 9
            'centro_costo_concepto_presupuesto_caja_10' => '', // Columna 89 - Centro Costo Concepto Presupuesto Caja 10
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 90 - Monto Moneda Base Centro Costo 10
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '', // Columna 91 - Monto Moneda Adicional Centro Costo 10
        ];
    }

    /**
     * Formatea la descripción del movimiento AC (DEBE)
     */
    private function formatACDescription(Payment $payment, $participant, $paymentOption): string
    {
        $documentType = $payment->document_type ?? 'AC';
        $authCode = $payment->authorization_code ?? ($payment->buy_order ?? $payment->id);
        $participantName = $participant ? ($participant->full_name ?? 'N/A') : 'PARTICIPANTE-NO-ENCONTRADO';
        $paymentMethod = $paymentOption ? ($paymentOption->report_code ?? 'SIN-METODO') : 'SIN-METODO';

        return "{$documentType}-{$authCode}-{$participantName}/{$paymentMethod}";
    }

    /**
     * Formatea la descripción del movimiento AC (HABER)
     * Formato: program_code-participant_document/emergency_contact_name/AC
     */
    private function formatACCreditDescription(Payment $payment, $participant, $program): string
    {
        $programCode = $program ? ($program->code ?? 'SIN-CODIGO') : 'SIN-CODIGO';

        // Obtener documento del participante limpio
        $participantDocument = '';
        if ($participant && $participant->document_number) {
            $participantDocument = str_replace(['.', '-'], '', $participant->document_number);
        }

        // Obtener nombre del contacto de emergencia en capital case
        $emergencyContactName = '';
        if ($participant && $participant->emergencyContacts && $participant->emergencyContacts->count() > 0) {
            $emergencyContactName = ucwords(strtolower($participant->emergencyContacts->first()->name ?? ''));
        }

        return "{$programCode}-{$participantDocument}/{$emergencyContactName}/AC";
    }


    private function getDocumentNumber($payment): string
    {
        // Si es pago con Khipu, usar external_payment_id si existe
        if ($payment->paymentOption && $payment->paymentOption->report_code === 'KP' && !empty($payment->external_payment_id)) {
            return $payment->external_payment_id;
        }

        // Si no es Khipu o no tiene external_payment_id, usar authorization_code
        return $payment->authorization_code ?? ($payment->buy_order ?? (string)$payment->id);
    }
}
