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
            'order.participant',
            'order.programCourse',
            'order.orderDetails.documentType',
            'order.participantProgram',
            'paymentOption',
            'paymentGateway',
        ])
        ->where('status', 'completed')
        ->where('document_type', 'B2'); // B2: presenciales + pasarela

        // Aplicar filtros de fecha si están presentes
        if (isset($filters['dateFrom'])) {
            $query->whereDate('transaction_date', '>=', $filters['dateFrom']);
        }

        if (isset($filters['dateTo'])) {
            $query->whereDate('transaction_date', '<=', $filters['dateTo']);
        }

        if (isset($filters['programId'])) {
            $query->whereHas('order', function ($subQ) use ($filters) {
                $subQ->where('program_id', $filters['programId']);
            });
        }

        $payments = $query->orderBy('transaction_date')->get();

        Log::info('SoftlandB2DataService: Pagos B2 encontrados', [
            'total_payments' => $payments->count()
        ]);

        $debitMovements = collect();
        $creditMovements = collect();

        foreach ($payments as $payment) {
            // DEBE: 1-1-02-010 (cargo boleta)
            $debitMovements->push($this->createDebitMovement($payment));

            // HABER: 3-1-01-021 (ingreso)
            $creditMovements->push($this->createCreditMovement($payment));
        }

        // Primero todos los DEBE, luego todos los HABER
        $movements = $debitMovements->concat($creditMovements);

        Log::info('SoftlandB2DataService: Movimientos B2 generados', [
            'total_payments' => $payments->count(),
            'total_movements' => $movements->count()
        ]);

        return $movements;
    }

    /**
     * DEBE: Cargo boleta - cuenta 1-1-02-010
     */
    private function createDebitMovement(Payment $payment): array
    {
        $amount = (int) abs($payment->amount);
        $boletaNumber = $payment->bsale_number ?? '';
        $buyerName = $this->getBuyerName($payment);
        $auxiliaryCode = $this->formatAuxiliaryCode($payment);
        $fecha = $this->formatDateDDMMYYYY($payment->created_at);

        // Descripción: "B2-{boleta} {Nombre Pagador}"
        $description = "B2-{$boletaNumber} {$buyerName}";

        return $this->buildRow([
            'codigo_plan_cuenta' => '1-1-02-010',
            'debe' => $amount,
            'haber' => 0,
            'descripcion_movimiento' => $description,
            'codigo_auxiliar' => $auxiliaryCode,
            'tipo_documento' => 'B2',
            'nro_documento' => $boletaNumber,
            'fecha_emision_docto' => $fecha,
            'fecha_vencimiento_docto' => $fecha,
            'tipo_docto_referencia' => 'B2',
            'nro_docto_referencia' => $boletaNumber,
            'monto_2_detalle_libro' => $amount,
            'monto_suma_detalle_libro' => $amount,
            'graba_detalle_libro' => 'S',
        ]);
    }

    /**
     * HABER: Ingreso - cuenta 3-1-01-021
     */
    private function createCreditMovement(Payment $payment): array
    {
        $amount = (int) abs($payment->amount);
        $boletaNumber = $payment->bsale_number ?? '';
        $programCode = $this->getProgramCode($payment);

        // Descripción: "{código_programa} B2-{boleta}"
        $description = "{$programCode} B2-{$boletaNumber}";

        return $this->buildRow([
            'codigo_plan_cuenta' => '3-1-01-021',
            'debe' => 0,
            'haber' => $amount,
            'descripcion_movimiento' => $description,
            'codigo_centro_costo' => 'E2-02-01',
        ]);
    }

    /**
     * Construye una fila completa de 91 columnas con valores por defecto vacíos
     */
    private function buildRow(array $data): array
    {
        $row = [
            'codigo_plan_cuenta' => '',      // A - Col 1
            'debe' => '',                     // B - Col 2
            'haber' => '',                    // C - Col 3
            'descripcion_movimiento' => '',   // D - Col 4
            'equivalencia_moneda' => '',      // E - Col 5
            'monto_debe_moneda_adicional' => '',  // F - Col 6
            'monto_haber_moneda_adicional' => '', // G - Col 7
            'codigo_condicion_venta' => '',   // H - Col 8
            'codigo_vendedor' => '',          // I - Col 9
            'codigo_ubicacion' => '',         // J - Col 10
            'codigo_concepto_caja' => '',     // K - Col 11
            'codigo_instrumento_financiero' => '', // L - Col 12
            'cantidad_instrumento_financiero' => '', // M - Col 13
            'codigo_detalle_gasto' => '',     // N - Col 14
            'cantidad_concepto_gasto' => '',  // O - Col 15
            'codigo_centro_costo' => '',      // P - Col 16
            'tipo_docto_conciliacion' => '',  // Q - Col 17
            'nro_docto_conciliacion' => '',   // R - Col 18
            'codigo_auxiliar' => '',          // S - Col 19
            'tipo_documento' => '',           // T - Col 20
            'nro_documento' => '',            // U - Col 21
            'fecha_emision_docto' => '',      // V - Col 22
            'fecha_vencimiento_docto' => '',  // W - Col 23
            'tipo_docto_referencia' => '',    // X - Col 24
            'nro_docto_referencia' => '',     // Y - Col 25
            'nro_correlativo_interno' => '',  // Z - Col 26
            'monto_1_detalle_libro' => '',    // AA - Col 27
            'monto_2_detalle_libro' => '',    // AB - Col 28
            'monto_3_detalle_libro' => '',    // AC - Col 29
            'monto_4_detalle_libro' => '',    // AD - Col 30
            'monto_5_detalle_libro' => '',    // AE - Col 31
            'monto_6_detalle_libro' => '',    // AF - Col 32
            'monto_7_detalle_libro' => '',    // AG - Col 33
            'monto_8_detalle_libro' => '',    // AH - Col 34
            'monto_9_detalle_libro' => '',    // AI - Col 35
            'monto_suma_detalle_libro' => '', // AJ - Col 36
            'graba_detalle_libro' => '',      // AK - Col 37
            'documento_nulo' => '',           // AL - Col 38
            'codigo_flujo_efectivo_1' => '',  // AM - Col 39
            'monto_flujo_1' => '',            // AN - Col 40
            'codigo_flujo_efectivo_2' => '',  // AO - Col 41
            'monto_flujo_2' => '',            // AP - Col 42
            'codigo_flujo_efectivo_3' => '',  // AQ - Col 43
            'monto_flujo_3' => '',            // AR - Col 44
            'codigo_flujo_efectivo_4' => '',  // AS - Col 45
            'monto_flujo_4' => '',            // AT - Col 46
            'codigo_flujo_efectivo_5' => '',  // AU - Col 47
            'monto_flujo_5' => '',            // AV - Col 48
            'codigo_flujo_efectivo_6' => '',  // AW - Col 49
            'monto_flujo_6' => '',            // AX - Col 50
            'codigo_flujo_efectivo_7' => '',  // AY - Col 51
            'monto_flujo_7' => '',            // AZ - Col 52
            'codigo_flujo_efectivo_8' => '',  // BA - Col 53
            'monto_flujo_8' => '',            // BB - Col 54
            'codigo_flujo_efectivo_9' => '',  // BC - Col 55
            'monto_flujo_9' => '',            // BD - Col 56
            'codigo_flujo_efectivo_10' => '', // BE - Col 57
            'monto_flujo_10' => '',           // BF - Col 58
            'numero_cuota_pago' => '',        // BG - Col 59
            'numero_documento_desde' => '',   // BH - Col 60
            'numero_documento_hasta' => '',   // BI - Col 61
            'centro_costo_1' => '',           // BJ - Col 62
            'monto_base_1' => '',             // BK - Col 63
            'monto_adicional_1' => '',        // BL - Col 64
            'centro_costo_2' => '',           // BM - Col 65
            'monto_base_2' => '',             // BN - Col 66
            'monto_adicional_2' => '',        // BO - Col 67
            'centro_costo_3' => '',           // BP - Col 68
            'monto_base_3' => '',             // BQ - Col 69
            'monto_adicional_3' => '',        // BR - Col 70
            'centro_costo_4' => '',           // BS - Col 71
            'monto_base_4' => '',             // BT - Col 72
            'monto_adicional_4' => '',        // BU - Col 73
            'centro_costo_5' => '',           // BV - Col 74
            'monto_base_5' => '',             // BW - Col 75
            'monto_adicional_5' => '',        // BX - Col 76
            'centro_costo_6' => '',           // BY - Col 77
            'monto_base_6' => '',             // BZ - Col 78
            'monto_adicional_6' => '',        // CA - Col 79
            'centro_costo_7' => '',           // CB - Col 80
            'monto_base_7' => '',             // CC - Col 81
            'monto_adicional_7' => '',        // CD - Col 82
            'centro_costo_8' => '',           // CE - Col 83
            'monto_base_8' => '',             // CF - Col 84
            'monto_adicional_8' => '',        // CG - Col 85
            'centro_costo_9' => '',           // CH - Col 86
            'monto_base_9' => '',             // CI - Col 87
            'monto_adicional_9' => '',        // CJ - Col 88
            'centro_costo_10' => '',          // CK - Col 89
            'monto_base_10' => '',            // CL - Col 90
            'monto_adicional_10' => '',       // CM - Col 91
        ];

        // Sobreescribir con los valores proporcionados
        foreach ($data as $key => $value) {
            if (array_key_exists($key, $row)) {
                $row[$key] = $value;
            }
        }

        return $row;
    }

    /**
     * Obtiene el nombre del comprador/pagador con primera letra mayúscula
     */
    private function getBuyerName(Payment $payment): string
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
     * Obtiene el código del programa (ej: V0036)
     */
    private function getProgramCode(Payment $payment): string
    {
        return $payment->order?->programCourse?->code ?? '';
    }

    /**
     * Formatea el código auxiliar: RUT sin DV, sin puntos, sin guiones
     */
    private function formatAuxiliaryCode(Payment $payment): string
    {
        $orderDetail = $payment->order?->orderDetails?->first();
        if (!$orderDetail || !$orderDetail->document_number) {
            return '';
        }

        $documentNumber = $orderDetail->document_number;

        // Si es pasaporte, usar tal como está
        if ($orderDetail->documentType && $orderDetail->documentType->name === 'PASAPORTE') {
            return strtoupper($documentNumber);
        }

        // Si es RUT, quitar puntos, guiones y dígito verificador
        $cleanNumber = preg_replace('/[^0-9kK]/', '', $documentNumber);
        if (strlen($cleanNumber) >= 2) {
            return substr($cleanNumber, 0, -1);
        }

        return $cleanNumber;
    }

    /**
     * Formatea fecha en DD-MM-YYYY
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
