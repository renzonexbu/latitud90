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
        // LOG: Filtros recibidos en el servicio
        Log::info('========== SOFTLAND DATA SERVICE ==========');
        Log::info('SoftlandDataService::generateMovements - Filtros recibidos:', [
            'filters' => $filters,
            'dateFrom' => $filters['dateFrom'] ?? 'NO DEFINIDO',
            'dateTo' => $filters['dateTo'] ?? 'NO DEFINIDO',
            'programId' => $filters['programId'] ?? 'NO DEFINIDO',
        ]);

        // Construir query y loggear SQL
        $query = Payment::with(['paymentOption', 'order.participant.emergencyContacts', 'order.program', 'order.participantProgram', 'order.orderDetails', 'orderDetail', 'paymentGateway'])
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('document_type', ['B2', 'AC'])
            // Excluir pagos que ya se procesan como cuotas de suscripción (evitar duplicados)
            ->whereNotIn('id', function ($q) {
                $q->select('payment_id')
                    ->from('installments')
                    ->where('is_paid', true)
                    ->whereNotNull('payment_id');
            })
            ->when(isset($filters['dateFrom']), function ($q) use ($filters) {
                $q->whereDate('transaction_date', '>=', $filters['dateFrom']);
            })
            ->when(isset($filters['dateTo']), function ($q) use ($filters) {
                $q->whereDate('transaction_date', '<=', $filters['dateTo']);
            })
            ->when(isset($filters['programId']), function ($q) use ($filters) {
                $q->whereHas('order', function ($subQ) use ($filters) {
                    $subQ->where('program_id', $filters['programId']);
                });
            })
            ->orderBy('transaction_date');

        // LOG: SQL que se ejecutará
        Log::info('SoftlandDataService - SQL Query:', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        // Obtener SOLO pagos completados que generaron boleta (B2) o anticipo (AC) y NO son presenciales
        $payments = $query->get();

        Log::info('SoftlandDataService - Pagos encontrados:', [
            'total' => $payments->count(),
        ]);

        // LOG: Mostrar todos los pagos obtenidos con sus datos
        Log::info('============================================');
        Log::info('SOFTLAND: PAGOS OBTENIDOS DE LA BASE DE DATOS');
        Log::info('============================================');
        foreach ($payments as $payment) {
            $participant = $payment->order?->participant;
            $payer = $payment->orderDetail;
            $gateway = $payment->paymentGateway;
            $program = $payment->order?->programCourse;

            // Obtener enrollment_code
            $enrollmentCode = 'N/A';
            if ($payment->order && $payment->order->participant_id && $payment->order->program_id) {
                $participantProgram = \App\Models\ParticipantProgram::where('participant_id', $payment->order->participant_id)
                    ->where('program_id', $payment->order->program_id)
                    ->first();
                $enrollmentCode = $participantProgram?->enrollment_code ?? 'N/A';
            }

            Log::info("Payment #{$payment->id}:", [
                'document_type' => $payment->document_type,
                'amount' => $payment->amount,
                'transaction_date' => $payment->transaction_date?->format('Y-m-d'),
                'authorization_code' => $payment->authorization_code ?? 'NULL',
                'gateway' => $gateway?->code ?? 'NULL',
                'payment_option' => $payment->paymentOption?->code ?? 'NULL',
                'participant_id' => $participant?->id ?? 'NULL',
                'participant_name' => $participant?->full_name ?? 'NULL',
                'payer_name' => $payer?->name ?? 'NULL',
                'program_id' => $program?->id ?? 'NULL',
                'program_name' => $program?->name ?? 'NULL',
                'enrollment_code' => $enrollmentCode,
            ]);
        }
        Log::info('============================================');

        // Obtener installments pagados (cuotas de suscripciones)
        $installments = $this->getInstallments($filters);

        // LOG: Mostrar todos los installments obtenidos
        Log::info('============================================');
        Log::info('SOFTLAND: INSTALLMENTS OBTENIDOS DE LA BASE DE DATOS');
        Log::info('============================================');
        foreach ($installments as $installment) {
            $participant = $installment['participant'] ?? null;
            $buyerData = $installment['buyer_data'] ?? [];
            $payment = $installment['payment'] ?? null;

            Log::info("Installment #{$installment['installment_id']}:", [
                'amount' => $installment['amount'],
                'paid_at' => $installment['paid_at'] ?? 'NULL',
                'payment_id' => $installment['payment_id'] ?? 'NULL',
                'document_type' => $installment['document_type'] ?? 'NULL',
                'participant_id' => $participant?->id ?? 'NULL',
                'participant_name' => $participant?->full_name ?? 'NULL',
                'payer_first_name' => $buyerData['first_name'] ?? 'NULL',
                'payer_last_name' => $buyerData['first_last_name'] ?? 'NULL',
                'gateway' => $payment?->paymentGateway?->code ?? 'NULL',
            ]);
        }
        Log::info('============================================');

        $movements014 = collect();  // 1-1-02-014 (cargo pagador DEBE - pasarela)
        $movements034 = collect();  // 1-1-01-034 (cargo pagador DEBE - banco: TE, DP)
        $movements009 = collect();  // 1-1-02-009 (cargo pagador DEBE - oficina: TC, WP, VP)
        $movements010 = collect();  // 1-1-02-010 (abono boleta HABER)
        $movementsOther = collect(); // AC, reembolsos, etc.

        // Códigos presenciales que van a cuenta bancaria (1-1-01-034)
        $bankCodes = ['presential_bank_transfer', 'presential_deposit'];
        // Códigos presenciales que van a cuenta oficina (1-1-02-009)
        $officeCodes = ['presential_pos_office', 'presential_webpay', 'presential_debit_credit'];

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
                $movementsOther->push($this->createRefundDebitMovement($payment));
                $movementsOther->push($this->createRefundCreditMovement($payment));
            } else {
                if ($payment->document_type === 'AC') {
                    $movementsOther->push($this->createACDebitMovement($payment));
                    $movementsOther->push($this->createACCreditMovement($payment));
                } else {
                    $optionCode = $payment->paymentOption->code ?? '';

                    if (in_array($optionCode, $bankCodes)) {
                        // Presencial banco: cuenta 1-1-01-034
                        $movements034->push($this->createPresentialDebitMovement($payment, '1-1-01-034'));
                        $movements010->push($this->createPaymentCreditMovement($payment));
                    } elseif (in_array($optionCode, $officeCodes)) {
                        // Presencial oficina: cuenta 1-1-02-009
                        $movements009->push($this->createPresentialDebitMovement($payment, '1-1-02-009'));
                        $movements010->push($this->createPaymentCreditMovement($payment));
                    } else {
                        // Pasarela: cuenta 1-1-02-014
                        $movements014->push($this->createPaymentDebitMovement($payment));
                        $movements010->push($this->createPaymentCreditMovement($payment));
                    }
                }
            }
        }

        // Procesar installments (cuotas de suscripciones como B2)
        foreach ($installments as $installment) {
            Log::info('SoftlandDataService: Procesando installment', [
                'installment_id' => $installment['installment_id'],
                'amount' => $installment['amount'],
            ]);

            $documentType = $installment['document_type'] ?? 'B2';

            if ($documentType === 'AC') {
                $movementsOther->push($this->createInstallmentDebitMovement($installment));
                $movementsOther->push($this->createInstallmentCreditMovement($installment));
            } else {
                // Comprobante 2: cargo pagador (014) + abono boleta pagada (010)
                $movements014->push($this->createInstallmentPaymentDebitMovement($installment));
                $movements010->push($this->createInstallmentPaymentCreditMovement($installment));
            }
        }

        // Agrupar por cuenta: 014, 034, 009, luego 010, luego otros
        $movements = $movements014
            ->concat($movements034)
            ->concat($movements009)
            ->concat($movements010)
            ->concat($movementsOther);

        Log::info('SoftlandDataService: Movimientos generados', [
            'total_payments' => $payments->count(),
            'total_installments' => $installments->count(),
            'total_014' => $movements014->count(),
            'total_034' => $movements034->count(),
            'total_009' => $movements009->count(),
            'total_010' => $movements010->count(),
            'total_other' => $movementsOther->count(),
            'total_movements' => $movements->count(),
            'organization' => '014, 034, 009, 010, otros'
        ]);

        return $movements;
    }

    /**
     * Crea el movimiento de cargo boleta (DEBE) - Comprobante 1
     * Cuenta 1-1-02-010, código auxiliar = pagador/apoderado sin DV, tipo doc = B2/FF
     */
    private function createDebitMovement(Payment $payment): array
    {
        $documentType = $payment->document_type ?? 'B2';
        $boletaNumber = $payment->bsale_number ?? $payment->buy_order ?? $payment->id;
        $participantName = $this->getPayerName($payment);

        return [
            // Información básica
            'codigo_plan_cuenta' => '1-1-02-010',
            'debe' => (int) abs($payment->amount),
            'haber' => 0,
            'descripcion_movimiento' => "{$documentType}-{$boletaNumber} {$participantName}",
            'equivalencia_moneda' => '',
            'monto_debe_moneda_adicional' => '',
            'monto_haber_moneda_adicional' => '',

            // Códigos (columnas 8-16)
            'codigo_condicion_venta' => '',
            'codigo_vendedor' => '',
            'codigo_ubicacion' => '',
            'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '',
            'cantidad_instrumento_financiero' => '',
            'codigo_detalle_gasto' => '',
            'cantidad_concepto_gasto' => '',
            'codigo_centro_costo' => '',

            // Documentación (columnas 17-26)
            'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '',
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment),
            'tipo_documento' => $documentType,
            'nro_documento' => $boletaNumber,
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'tipo_docto_referencia' => $documentType,
            'nro_docto_referencia' => $boletaNumber,
            'nro_correlativo_interno' => '',

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
        $program = $payment->order->programCourse;
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
     * Formatea la descripción del movimiento de ingreso (HABER) - Comprobante 1
     * Formato: "{programCode} {documentType}-{boletaNumber}" (sin N al inicio, sin VP, con tipo doc emitido)
     */
    private function formatCreditDescription(Payment $payment, $participant, $program, $paymentOption): string
    {
        $programCode = $program ? ($program->code ?? 'SIN-CODIGO') : 'SIN-CODIGO';
        $documentType = $payment->document_type ?? 'B2'; // Tipo doc emitido (B2/FF/VC), NO VP
        $boletaNumber = $payment->bsale_number ?? ($payment->buy_order ?? $payment->id);

        return "{$programCode} {$documentType}-{$boletaNumber}";
    }

    /**
     * Comprobante 2 - Cargo pagador presencial (DEBE) - Cuenta variable (034 o 009)
     * Nro documento: código de autorización del pago offline
     */
    private function createPresentialDebitMovement(Payment $payment, string $accountCode): array
    {
        $orderDetail = $payment->orderDetail ?? $payment->order->orderDetails->first();
        $payerName = $orderDetail ? ucwords(strtolower($orderDetail->name ?? '')) : '';
        $paymentMethod = $this->getPaymentMethodCode($payment);
        $authCode = $payment->authorization_code ?? (string) $payment->id;

        return $this->buildMovementRow([
            'codigo_plan_cuenta' => $accountCode,
            'debe' => (int) abs($payment->amount),
            'haber' => 0,
            'descripcion_movimiento' => $payerName,
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment),
            'tipo_documento' => $paymentMethod,
            'nro_documento' => $authCode,
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'tipo_docto_referencia' => $paymentMethod,
            'nro_docto_referencia' => $authCode,
        ]);
    }

    /**
     * Comprobante 2 - Cargo pagador (DEBE) - Cuenta 1-1-02-014
     * Descripción: nombre del pagador solamente
     * Código auxiliar: RUT pagador sin DV
     * Tipo doc: método de pago (VP/KH/TC/TE/DP)
     * Nro doc: ID transacción
     */
    private function createPaymentDebitMovement(Payment $payment): array
    {
        $orderDetail = $payment->orderDetail ?? $payment->order->orderDetails->first();
        $payerName = $orderDetail ? ucwords(strtolower($orderDetail->name ?? '')) : '';
        $paymentMethod = $this->getPaymentMethodCode($payment);
        $transactionId = $this->getTransactionId($payment);
        $accountCode = $this->getAccountCodeByPaymentMethod($paymentMethod);

        return $this->buildMovementRow([
            'codigo_plan_cuenta' => $accountCode,
            'debe' => (int) abs($payment->amount),
            'haber' => 0,
            'descripcion_movimiento' => $payerName,
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment),
            'tipo_documento' => $paymentMethod,
            'nro_documento' => $transactionId,
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'tipo_docto_referencia' => $paymentMethod,
            'nro_docto_referencia' => $transactionId,
        ]);
    }

    /**
     * Comprobante 2 - Abono boleta pagada (HABER) - Cuenta 1-1-02-010
     * Descripción: tipo+nro doc + nombre alumno + método pago
     * Código auxiliar: RUT pagador/apoderado sin DV
     * Tipo doc: método de pago
     * Nro doc: ID transacción
     * Tipo doc referencia: B2/FF (tipo doc emitido)
     * Nro doc referencia: nro boleta
     */
    private function createPaymentCreditMovement(Payment $payment): array
    {
        $payerName = $this->getPayerName($payment);
        $documentType = $payment->document_type ?? 'B2';
        $boletaNumber = $payment->bsale_number ?? $payment->buy_order ?? $payment->id;
        $paymentMethod = $this->getPaymentMethodCode($payment);
        $transactionId = $this->getTransactionId($payment);

        return $this->buildMovementRow([
            'codigo_plan_cuenta' => '1-1-02-010',
            'debe' => 0,
            'haber' => (int) abs($payment->amount),
            'descripcion_movimiento' => "{$documentType}-{$boletaNumber} {$payerName} / {$paymentMethod}",
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment),
            'tipo_documento' => $paymentMethod,
            'nro_documento' => $transactionId,
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'tipo_docto_referencia' => $documentType,
            'nro_docto_referencia' => $boletaNumber,
        ]);
    }

    /**
     * Construye una fila de movimiento con todos los campos, usando defaults vacíos
     */
    private function buildMovementRow(array $data): array
    {
        $defaults = [
            'codigo_plan_cuenta' => '', 'debe' => 0, 'haber' => 0,
            'descripcion_movimiento' => '', 'equivalencia_moneda' => '',
            'monto_debe_moneda_adicional' => '', 'monto_haber_moneda_adicional' => '',
            'codigo_condicion_venta' => '', 'codigo_vendedor' => '',
            'codigo_ubicacion' => '', 'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '', 'cantidad_instrumento_financiero' => '',
            'codigo_detalle_gasto' => '', 'cantidad_concepto_gasto' => '',
            'codigo_centro_costo' => '', 'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '', 'codigo_auxiliar' => '',
            'tipo_documento' => '', 'nro_documento' => '',
            'fecha_emision_docto' => '', 'fecha_vencimiento_docto' => '',
            'tipo_docto_referencia' => '', 'nro_docto_referencia' => '',
            'nro_correlativo_interno' => '',
            'monto_1_detalle_libro' => '', 'monto_2_detalle_libro' => '',
            'monto_3_detalle_libro' => '', 'monto_4_detalle_libro' => '',
            'monto_5_detalle_libro' => '', 'monto_6_detalle_libro' => '',
            'monto_7_detalle_libro' => '', 'monto_8_detalle_libro' => '',
            'monto_9_detalle_libro' => '', 'monto_suma_detalle_libro' => '',
            'graba_detalle_libro' => '', 'documento_nulo' => '',
            'codigo_flujo_efectivo_1' => '', 'monto_flujo_1' => '',
            'codigo_flujo_efectivo_2' => '', 'monto_flujo_2' => '',
            'codigo_flujo_efectivo_3' => '', 'monto_flujo_3' => '',
            'codigo_flujo_efectivo_4' => '', 'monto_flujo_4' => '',
            'codigo_flujo_efectivo_5' => '', 'monto_flujo_5' => '',
            'codigo_flujo_efectivo_6' => '', 'monto_flujo_6' => '',
            'codigo_flujo_efectivo_7' => '', 'monto_flujo_7' => '',
            'codigo_flujo_efectivo_8' => '', 'monto_flujo_8' => '',
            'codigo_flujo_efectivo_9' => '', 'monto_flujo_9' => '',
            'codigo_flujo_efectivo_10' => '', 'monto_flujo_10' => '',
            'numero_cuota_pago' => '', 'numero_documento_desde' => '',
            'numero_documento_hasta' => '',
            'centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '',
            'centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '',
            'centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '',
            'centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '',
            'centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '',
            'centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '',
            'centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '',
            'centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '',
            'centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '',
            'centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '',
        ];

        return array_merge($defaults, $data);
    }

    /**
     * Quita puntos, guiones y dígito verificador de un RUT para código auxiliar Softland
     */
    private function removeRutDV(string $documentNumber): string
    {
        $clean = preg_replace('/[^0-9kK]/', '', $documentNumber);
        if (strlen($clean) >= 2) {
            return substr($clean, 0, -1); // Sin dígito verificador
        }
        return strtoupper($clean);
    }


    /**
     * Código auxiliar del PAGADOR (order_detail) sin DV
     */
    private function formatPayerAuxiliaryCode($payment): string
    {
        $orderDetail = $payment->order->orderDetails->first() ?? null;
        if (!$orderDetail || !$orderDetail->document_number) {
            return '';
        }
        return $this->removeRutDV($orderDetail->document_number);
    }

    /**
     * Obtiene el nombre del pagador desde buyer_data (suscripciones/installments), fallback al participante
     */
    private function getBuyerNameFromData(array $buyerData, $participant): string
    {
        if (!empty($buyerData['first_name']) && !empty($buyerData['first_last_name'])) {
            $name = $buyerData['first_name'] . ' ' . $buyerData['first_last_name'];
            if (!empty($buyerData['second_last_name'])) {
                $name .= ' ' . $buyerData['second_last_name'];
            }
            return mb_convert_case(trim($name), MB_CASE_TITLE, 'UTF-8');
        }

        if ($participant && !empty($participant->full_name)) {
            return mb_convert_case(trim($participant->full_name), MB_CASE_TITLE, 'UTF-8');
        }

        return '';
    }

    /**
     * Obtiene el nombre del pagador/apoderado desde orderDetail, fallback al participante
     */
    private function getPayerName(Payment $payment): string
    {
        $orderDetail = $payment->order?->orderDetails?->first();

        if ($orderDetail && !empty($orderDetail->name)) {
            return mb_convert_case(trim($orderDetail->name), MB_CASE_TITLE, 'UTF-8');
        }

        $participant = $payment->order?->participant;
        if ($participant && !empty($participant->full_name)) {
            return mb_convert_case(trim($participant->full_name), MB_CASE_TITLE, 'UTF-8');
        }

        return '';
    }

    /**
     * Obtiene el método de pago en 2 letras para Softland.
     * PAT y VPI se muestran como VP (así lo pide contabilidad).
     */
    private function getPaymentMethodCode($payment): string
    {
        if ($payment->paymentOption) {
            $code = $payment->paymentOption->report_code ?? 'VP';
            // PAT (suscripciones) y VPI (VirtualPos Internacional) → VP
            if ($code === 'PAT' || $code === 'VPI') {
                return 'VP';
            }
            // Softland solo acepta 2 letras
            return substr($code, 0, 2);
        }
        return 'VP';
    }

    /**
     * Devuelve el código de Plan de Cuentas Softland según el método de pago.
     * Tabla oficial provista por Carmen (contabilidad):
     *   - AC (anticipo)                  → 2-1-04-051
     *   - TC (tarjeta crédito presencial) → 1-1-02-009
     *   - WP/KP/VP (incluye PAT/VPI)     → 1-1-02-014
     *   - TE/DP (transferencia/depósito) → 1-1-01-039
     * Cualquier código desconocido cae al 1-1-02-014 (default histórico).
     */
    private function getAccountCodeByPaymentMethod(string $paymentMethod): string
    {
        return match (strtoupper($paymentMethod)) {
            'AC'                            => '2-1-04-051',
            'TC'                            => '1-1-02-009',
            'TE', 'DP'                      => '1-1-01-039',
            'WP', 'KP', 'VP', 'PAT', 'VPI'  => '1-1-02-014',
            default                         => '1-1-02-014',
        };
    }

    /**
     * Obtiene el ID de transacción del gateway
     */
    private function getTransactionId($payment): string
    {
        // Intentar obtener uuid del gateway_response de virtualpos
        $gatewayResponse = $payment->gateway_response;
        if (is_string($gatewayResponse)) {
            $gatewayResponse = json_decode($gatewayResponse, true);
        }

        // VirtualPos: uuid está en payment.order.uuid
        $uuid = $gatewayResponse['payment']['order']['uuid'] ?? null;
        if ($uuid) {
            return substr($uuid, 0, 8);
        }

        // Khipu: usar external_payment_id
        if (!empty($payment->external_payment_id)) {
            return substr($payment->external_payment_id, 0, 8);
        }

        // Fallback: authorization_code o buy_order
        $fallback = $payment->authorization_code ?? $payment->buy_order ?? (string) $payment->id;
        return substr($fallback, 0, 8);
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
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment), // Columna S - RUT del comprador sin puntos/guiones
            'tipo_documento' => 'NC', // Columna T - Nota de crédito
            'nro_documento' => 'REEMB-' . ($payment->bsale_number ?? ($payment->buy_order ?? $payment->id)), // Columna U - bsale_number
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date), // Columna W - formato DD-MM-YYYY
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
        $program = $payment->order->programCourse ?? null;
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
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment), // Columna S - RUT del comprador sin puntos/guiones
            'tipo_documento' => '', // Columna T - vacío para HABER
            'nro_documento' => '', // Columna U - vacío para HABER
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date), // Columna W - formato DD-MM-YYYY
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

        // Obtener el nombre del pagador/apoderado desde OrderDetail
        $payerName = '';
        $orderDetail = $payment->orderDetail;
        if ($orderDetail && $orderDetail->name) {
            $payerName = ucwords(strtolower($orderDetail->name));
        }

        // Determinar el tipo de documento según el gateway de pago
        $documentType = '';
        if ($payment->paymentGateway && $payment->paymentGateway->code === 'virtualpos') {
            $documentType = 'VP'; // VirtualPOS
        } else {
            $documentType = $paymentOption->report_code ?? '';
        }

        return [
            // Información básica
            'codigo_plan_cuenta' => '2-1-04-051', // Cuenta específica para AC (Anticipo) según tabla contable
            'debe' => (int) abs($payment->amount), // Mismo monto que haber sin decimales
            'haber' => 0, // Vacío para DEBE
            'descripcion_movimiento' => $payerName, // Solo el nombre del pagador/apoderado
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
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment), // Columna 19 - mismo que haber
            'tipo_documento' => $documentType, // Columna 20 - VP para VirtualPos, o payment_option.report_code
            'nro_documento' => $this->getDocumentNumber($payment), // Columna 21 - authorization_code
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'tipo_docto_referencia' => $documentType, // Columna 24 - VP para VirtualPos, o payment_option.report_code
            'nro_docto_referencia' => $this->getDocumentNumber($payment), // Columna 25 - authorization_code
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
        $program = $payment->order->programCourse;
        $paymentOption = $payment->paymentOption;

        // Determinar el tipo de documento según el gateway de pago
        $documentType = '';
        if ($payment->paymentGateway && $payment->paymentGateway->code === 'virtualpos') {
            $documentType = 'VP'; // VirtualPOS
        } else {
            $documentType = 'AC'; // Anticipo por defecto
        }

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
            'codigo_auxiliar' => $this->formatPayerAuxiliaryCode($payment), // Columna 19 - mismo que haber
            'tipo_documento' => $documentType, // Columna 20 - VP para VirtualPos, AC por defecto
            'nro_documento' => $this->getDocumentNumber($payment), // Columna 21 - authorization_code
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date), // Columna V - formato DD-MM-YYYY
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($payment->accounting_date ?? $payment->transaction_date),
            'tipo_docto_referencia' => $documentType, // Columna 24 - VP para VirtualPos, AC por defecto
            'nro_docto_referencia' => $this->getDocumentNumber($payment), // Columna 25 - authorization_code
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
     * Formato: enrollment_code/payer_name/document_type
     */
    private function formatACCreditDescription(Payment $payment, $participant, $program): string
    {
        // Obtener enrollment_code desde participant_program
        // Buscar usando participant_id y program_id del order
        $enrollmentCode = '';
        if ($payment->order && $payment->order->participant_id && $payment->order->program_id) {
            $participantProgram = \App\Models\ParticipantProgram::where('participant_id', $payment->order->participant_id)
                ->where('program_id', $payment->order->program_id)
                ->first();

            if ($participantProgram && $participantProgram->enrollment_code) {
                $enrollmentCode = $participantProgram->enrollment_code;
            }
        }

        // Obtener el nombre del pagador/apoderado desde OrderDetail
        $payerName = '';
        $orderDetail = $payment->orderDetail;
        if ($orderDetail && $orderDetail->name) {
            $payerName = ucwords(strtolower($orderDetail->name));
        }

        // Determinar el tipo de documento según el gateway de pago
        $documentType = '';
        if ($payment->paymentGateway && $payment->paymentGateway->code === 'virtualpos') {
            $documentType = 'VP'; // VirtualPOS
        } else {
            $documentType = 'AC'; // Anticipo por defecto
        }

        return "{$enrollmentCode}/{$payerName}/{$documentType}";
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

    /**
     * Obtener installments pagados (cuotas de suscripciones)
     */
    private function getInstallments(array $filters = []): Collection
    {
        $query = \App\Models\Installment::with([
            'installmentPlan.participant',
            'payment.paymentOption',
            'payment.paymentGateway',
        ])
        ->where('is_paid', true);

        // Aplicar filtros de fecha si están presentes
        if (isset($filters['dateFrom'])) {
            $query->whereDate('paid_at', '>=', $filters['dateFrom']);
        }

        if (isset($filters['dateTo'])) {
            $query->whereDate('paid_at', '<=', $filters['dateTo']);
        }

        if (isset($filters['programId'])) {
            $query->whereHas('installmentPlan', function ($q) use ($filters) {
                $q->where('program_id', $filters['programId']);
            });
        }

        $installments = $query->orderBy('paid_at')->get();

        // Transformar installments a formato estándar, filtrando solo suscripciones activas
        return $installments->map(function ($installment) {
            $plan = $installment->installmentPlan;
            $participant = $plan->participant ?? null;

            // Obtener el program_course
            $programCourse = \App\Models\ProgramCourse::with('course.institution')->find($plan->program_id);

            // Obtener la suscripción para los datos del comprador
            $subscription = $plan->programSubscription
                ?? \App\Models\ProgramSubscription::where('participant_id', $participant->id ?? null)
                    ->where('program_id', $plan->program_id)
                    ->first();

            // Solo incluir si la suscripción está activa
            if (!$subscription || $subscription->status !== 'ACTIVA') {
                return null;
            }

            $buyerData = $subscription->buyer_data ?? [];

            return [
                'installment_id' => $installment->id,
                'installment_number' => $installment->installment_number,
                'amount' => $installment->amount,
                'paid_at' => $installment->paid_at,
                'participant' => $participant,
                'program_course' => $programCourse,
                'buyer_data' => $buyerData,
                'virtualpos_charge_id' => $installment->virtualpos_charge_id,
                'payment' => $installment->payment,
                'authorization_code' => $installment->payment?->authorization_code,
                'document_type' => $installment->payment?->document_type ?? 'B2',
            ];
        })->filter();
    }

    /**
     * Crea el movimiento de cargo boleta de installment (DEBE) - Comprobante 1
     */
    private function createInstallmentDebitMovement(array $installment): array
    {
        $participant = $installment['participant'];
        $buyerData = $installment['buyer_data'];

        // Código auxiliar del ALUMNO sin DV
        $auxiliarCode = '';
        if ($participant && $participant->document_number) {
            $auxiliarCode = $this->removeRutDV($participant->document_number);
        }

        $documentType = $installment['document_type'] ?? 'B2';
        $payment = $installment['payment'] ?? null;

        // Nro. documento del movimiento:
        //  - B2: bsale_number (número de boleta)
        //  - AC: authorization_code del pago VirtualPos (lo pide contabilidad).
        //    Antes se usaba INST-{id} o virtualpos_charge_id, quedaba ilegible.
        if ($documentType === 'AC') {
            $boletaNumber = $payment->authorization_code
                ?? $installment['virtualpos_charge_id']
                ?? ('INST-' . $installment['installment_id']);
        } else {
            $boletaNumber = $payment->bsale_number
                ?? ($installment['virtualpos_charge_id'] ?? ('INST-' . $installment['installment_id']));
        }

        // Si el Payment tiene document_type='AC', usar cuenta 2-1-04-051 según tabla contable;
        // para B2 y demás, cuenta 1-1-02-010.
        $accountCode = ($documentType === 'AC') ? '2-1-04-051' : '1-1-02-010';

        // Nombre del pagador (buyer_data), fallback al participante
        $buyerName = $this->getBuyerNameFromData($buyerData, $participant);

        // Descripción según tipo
        if ($documentType === 'AC') {
            $description = $buyerName;
        } else {
            $description = "{$documentType}-{$boletaNumber} {$buyerName}";
        }

        return [
            // Información básica
            'codigo_plan_cuenta' => $accountCode,
            'debe' => (int) abs($installment['amount']),
            'haber' => 0,
            'descripcion_movimiento' => $description,
            'equivalencia_moneda' => '',
            'monto_debe_moneda_adicional' => '',
            'monto_haber_moneda_adicional' => '',

            // Códigos (columnas 8-16)
            'codigo_condicion_venta' => '',
            'codigo_vendedor' => '',
            'codigo_ubicacion' => '',
            'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '',
            'cantidad_instrumento_financiero' => '',
            'codigo_detalle_gasto' => '',
            'cantidad_concepto_gasto' => '',
            'codigo_centro_costo' => '',

            // Documentación (columnas 17-26)
            'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '',
            'codigo_auxiliar' => $auxiliarCode,
            'tipo_documento' => $documentType,
            'nro_documento' => $boletaNumber,
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($installment['paid_at']),
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($installment['paid_at']),
            'tipo_docto_referencia' => $documentType,
            'nro_docto_referencia' => $boletaNumber,
            'nro_correlativo_interno' => '',

            // Montos detalle libro (columnas 27-36)
            'monto_1_detalle_libro' => '',
            'monto_2_detalle_libro' => (int) abs($installment['amount']),
            'monto_3_detalle_libro' => '',
            'monto_4_detalle_libro' => '',
            'monto_5_detalle_libro' => '',
            'monto_6_detalle_libro' => '',
            'monto_7_detalle_libro' => '',
            'monto_8_detalle_libro' => '',
            'monto_9_detalle_libro' => '',
            'monto_suma_detalle_libro' => (int) abs($installment['amount']),

            // Configuración (columnas 37-38)
            'graba_detalle_libro' => 'S',
            'documento_nulo' => '',

            // Flujos de efectivo (columnas 39-58)
            'codigo_flujo_efectivo_1' => '',
            'monto_flujo_1' => '',
            'codigo_flujo_efectivo_2' => '',
            'monto_flujo_2' => '',
            'codigo_flujo_efectivo_3' => '',
            'monto_flujo_3' => '',
            'codigo_flujo_efectivo_4' => '',
            'monto_flujo_4' => '',
            'codigo_flujo_efectivo_5' => '',
            'monto_flujo_5' => '',
            'codigo_flujo_efectivo_6' => '',
            'monto_flujo_6' => '',
            'codigo_flujo_efectivo_7' => '',
            'monto_flujo_7' => '',
            'codigo_flujo_efectivo_8' => '',
            'monto_flujo_8' => '',
            'codigo_flujo_efectivo_9' => '',
            'monto_flujo_9' => '',
            'codigo_flujo_efectivo_10' => '',
            'monto_flujo_10' => '',

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '',
            'numero_documento_desde' => '',
            'numero_documento_hasta' => '',

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '',
            'centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '',
            'centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '',
            'centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '',
            'centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '',
            'centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '',
            'centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '',
            'centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '',
            'centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '',
            'centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '',
        ];
    }

    /**
     * Crea el movimiento de ingreso de installment (HABER)
     */
    private function createInstallmentCreditMovement(array $installment): array
    {
        $programCourse = $installment['program_course'];
        $authorizationCode = $installment['authorization_code'] ?? null;
        $documentType = $installment['document_type'] ?? 'B2';
        $documentNumber = $authorizationCode ?? ($installment['virtualpos_charge_id'] ?? ('INST-' . $installment['installment_id']));

        // Obtener código del programa
        $programCode = 'SIN-CODIGO';
        if ($programCourse) {
            // Obtener el program parent para el código
            $program = \App\Models\Program::find($programCourse->program_id);
            $programCode = $program?->code ?? 'SIN-CODIGO';
        }

        // Determinar cuenta según el document_type del Payment
        $payment = $installment['payment'] ?? null;
        $isVirtualPos = $payment && $payment->paymentGateway && $payment->paymentGateway->code === 'virtualpos';

        // Si es AC, va a cuenta 060, sino a 021
        $accountCode = ($documentType === 'AC') ? '2-1-04-060' : '3-1-01-021';

        // Si es VirtualPos, tipo de documento es VP
        $displayDocumentType = $isVirtualPos ? 'VP' : $documentType;

        // Descripción: para AC usar formato enrollment_code/payer_name/VP, para B2 el formato de programa
        $description = '';
        if ($documentType === 'AC') {
            // Para AC (cuenta 060): enrollment_code/payer_name/VP
            // Obtener enrollment_code
            $enrollmentCode = '';
            $participant = $installment['participant'] ?? null;
            if ($participant && $payment) {
                // Buscar ParticipantProgram para obtener enrollment_code
                $participantProgram = \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                    ->where('program_id', $programCourse?->program_id)
                    ->first();
                $enrollmentCode = $participantProgram?->enrollment_code ?? '';
            }

            // Obtener nombre del pagador
            $buyerData = $installment['buyer_data'] ?? [];
            $payerName = '';
            if (!empty($buyerData['first_name']) && !empty($buyerData['first_last_name'])) {
                $firstName = ucwords(strtolower($buyerData['first_name']));
                $lastName = ucwords(strtolower($buyerData['first_last_name']));
                $payerName = "{$firstName} {$lastName}";
            } elseif ($participant) {
                $payerName = $participant->full_name ?? '';
            }

            $description = "{$enrollmentCode}/{$payerName}/{$displayDocumentType}";
        } else {
            // Para B2 (cuenta 021): código programa + tipo doc emitido (sin N, sin VP)
            $boletaNumber = $payment->bsale_number ?? ($installment['virtualpos_charge_id'] ?? ('INST-' . $installment['installment_id']));
            $description = "{$programCode} {$documentType}-{$boletaNumber}";
        }

        return [
            // Información básica
            'codigo_plan_cuenta' => $accountCode,
            'debe' => 0,
            'haber' => (int) abs($installment['amount']),
            'descripcion_movimiento' => $description,
            'equivalencia_moneda' => '',
            'monto_debe_moneda_adicional' => '',
            'monto_haber_moneda_adicional' => '',

            // Códigos (columnas H-M vacías)
            'codigo_condicion_venta' => '',
            'codigo_vendedor' => '',
            'codigo_ubicacion' => '',
            'codigo_concepto_caja' => '',
            'codigo_instrumento_financiero' => '',
            'cantidad_instrumento_financiero' => '',
            'codigo_detalle_gasto' => '',
            'cantidad_concepto_gasto' => '',
            'codigo_centro_costo' => 'E2-02-01',

            // Documentación
            'tipo_docto_conciliacion' => '',
            'nro_docto_conciliacion' => '',
            'codigo_auxiliar' => '',
            'tipo_documento' => '',
            'nro_documento' => '',
            'fecha_emision_docto' => '',
            'fecha_vencimiento_docto' => '',
            'tipo_docto_referencia' => '',
            'nro_docto_referencia' => '',
            'nro_correlativo_interno' => '',

            // Montos detalle libro
            'monto_1_detalle_libro' => '',
            'monto_2_detalle_libro' => '',
            'monto_3_detalle_libro' => '',
            'monto_4_detalle_libro' => '',
            'monto_5_detalle_libro' => '',
            'monto_6_detalle_libro' => '',
            'monto_7_detalle_libro' => '',
            'monto_8_detalle_libro' => '',
            'monto_9_detalle_libro' => '',
            'monto_suma_detalle_libro' => '',

            // Configuración
            'graba_detalle_libro' => '',
            'documento_nulo' => '',

            // Flujos de efectivo (todas vacías)
            'codigo_flujo_efectivo_1' => '',
            'monto_flujo_1' => '',
            'codigo_flujo_efectivo_2' => '',
            'monto_flujo_2' => '',
            'codigo_flujo_efectivo_3' => '',
            'monto_flujo_3' => '',
            'codigo_flujo_efectivo_4' => '',
            'monto_flujo_4' => '',
            'codigo_flujo_efectivo_5' => '',
            'monto_flujo_5' => '',
            'codigo_flujo_efectivo_6' => '',
            'monto_flujo_6' => '',
            'codigo_flujo_efectivo_7' => '',
            'monto_flujo_7' => '',
            'codigo_flujo_efectivo_8' => '',
            'monto_flujo_8' => '',
            'codigo_flujo_efectivo_9' => '',
            'monto_flujo_9' => '',
            'codigo_flujo_efectivo_10' => '',
            'monto_flujo_10' => '',

            // Información adicional (columnas 59-61)
            'numero_cuota_pago' => '',
            'numero_documento_desde' => '',
            'numero_documento_hasta' => '',

            // Centros de costo concepto presupuesto caja (columnas 62-91)
            'centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_1' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_1' => '',
            'centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_2' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_2' => '',
            'centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_3' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_3' => '',
            'centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_4' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_4' => '',
            'centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_5' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_5' => '',
            'centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_6' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_6' => '',
            'centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_7' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_7' => '',
            'centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_8' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_8' => '',
            'centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_9' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_9' => '',
            'centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_base_centro_costo_concepto_presupuesto_caja_10' => '',
            'monto_moneda_adicional_centro_costo_concepto_presupuesto_caja_10' => '',
        ];
    }

    /**
     * Comprobante 2 - Cargo pagador installment (DEBE) - Cuenta 1-1-02-014
     */
    private function createInstallmentPaymentDebitMovement(array $installment): array
    {
        $buyerData = $installment['buyer_data'] ?? [];
        $participant = $installment['participant'];
        $payment = $installment['payment'] ?? null;

        // Nombre del pagador
        $payerName = '';
        if (!empty($buyerData['first_name']) && !empty($buyerData['first_last_name'])) {
            $payerName = ucwords(strtolower($buyerData['first_name'])) . ' ' . ucwords(strtolower($buyerData['first_last_name']));
        } elseif ($participant) {
            $payerName = $participant->full_name ?? '';
        }

        // RUT pagador sin DV
        $payerAuxiliarCode = '';
        if (!empty($buyerData['document_number'])) {
            $payerAuxiliarCode = $this->removeRutDV($buyerData['document_number']);
        }

        // Método de pago: suscripciones siempre VP (PAT y VPI mapean a VP)
        $paymentMethod = 'VP';
        if ($payment && $payment->paymentOption) {
            $code = $payment->paymentOption->report_code ?? 'VP';
            $paymentMethod = ($code === 'PAT' || $code === 'VPI') ? 'VP' : substr($code, 0, 2);
        }

        // ID transacción: primeros 8 dígitos del uuid de gateway_response
        $transactionId = $this->getInstallmentTransactionId($installment);

        return $this->buildMovementRow([
            'codigo_plan_cuenta' => $this->getAccountCodeByPaymentMethod($paymentMethod),
            'debe' => (int) abs($installment['amount']),
            'haber' => 0,
            'descripcion_movimiento' => $payerName,
            'codigo_auxiliar' => $payerAuxiliarCode,
            'tipo_documento' => $paymentMethod,
            'nro_documento' => $transactionId,
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($installment['paid_at']),
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($installment['paid_at']),
            'tipo_docto_referencia' => $paymentMethod,
            'nro_docto_referencia' => $transactionId,
        ]);
    }

    /**
     * Comprobante 2 - Abono boleta pagada installment (HABER) - Cuenta 1-1-02-010
     */
    private function createInstallmentPaymentCreditMovement(array $installment): array
    {
        $participant = $installment['participant'];
        $buyerData = $installment['buyer_data'] ?? [];
        $payment = $installment['payment'] ?? null;
        $documentType = $installment['document_type'] ?? 'B2';
        $boletaNumber = $payment->bsale_number ?? ($installment['virtualpos_charge_id'] ?? ('INST-' . $installment['installment_id']));

        // Nombre del pagador (buyer_data), fallback al participante
        $payerName = $this->getBuyerNameFromData($buyerData, $participant);

        // RUT pagador (buyer_data) sin DV, fallback al participante
        $payerAuxiliarCode = '';
        if (!empty($buyerData['document_number'])) {
            $payerAuxiliarCode = $this->removeRutDV($buyerData['document_number']);
        } elseif ($participant && $participant->document_number) {
            $payerAuxiliarCode = $this->removeRutDV($participant->document_number);
        }

        // Método de pago: suscripciones siempre VP (PAT y VPI mapean a VP)
        $paymentMethod = 'VP';
        if ($payment && $payment->paymentOption) {
            $code = $payment->paymentOption->report_code ?? 'VP';
            $paymentMethod = ($code === 'PAT' || $code === 'VPI') ? 'VP' : substr($code, 0, 2);
        }

        $transactionId = $this->getInstallmentTransactionId($installment);

        return $this->buildMovementRow([
            'codigo_plan_cuenta' => '1-1-02-010',
            'debe' => 0,
            'haber' => (int) abs($installment['amount']),
            'descripcion_movimiento' => "{$documentType}-{$boletaNumber} {$payerName} / {$paymentMethod}",
            'codigo_auxiliar' => $payerAuxiliarCode,
            'tipo_documento' => $paymentMethod,
            'nro_documento' => $transactionId,
            'fecha_emision_docto' => $this->formatDateDDMMYYYY($installment['paid_at']),
            'fecha_vencimiento_docto' => $this->formatDateDDMMYYYY($installment['paid_at']),
            'tipo_docto_referencia' => $documentType,
            'nro_docto_referencia' => $boletaNumber,
        ]);
    }

    /**
     * Obtiene el ID de transacción de un installment (primeros 8 dígitos del uuid)
     */
    private function getInstallmentTransactionId(array $installment): string
    {
        $payment = $installment['payment'] ?? null;

        if ($payment) {
            // Usar el uuid del gateway_response del payment
            $gatewayResponse = $payment->gateway_response;
            if (is_string($gatewayResponse)) {
                $gatewayResponse = json_decode($gatewayResponse, true);
            }

            $uuid = $gatewayResponse['payment']['order']['uuid'] ?? null;
            if ($uuid) {
                return substr($uuid, 0, 8);
            }
        }

        // Fallback: virtualpos_charge_id (quitar prefijo cid_)
        $chargeId = $installment['virtualpos_charge_id'] ?? '';
        if ($chargeId) {
            $clean = str_replace('cid_', '', $chargeId);
            return substr($clean, 0, 8);
        }

        return (string) $installment['installment_id'];
    }
}
