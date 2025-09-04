<?php

namespace App\Services\Admin\Reports\DailyPayments;

use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DailyPaymentsTransformer
{
    public function transformForView(LengthAwarePaginator $data): LengthAwarePaginator
    {
        $data->getCollection()->transform(function ($item) {
            return $this->transformPaymentItem($item);
        });
        
        return $data;
    }
    
    public function transformForExport(Collection $data, array $selectedFields = []): Collection
    {
        return $data->map(function ($item) use ($selectedFields) {
            return $this->transformForExportRow($item, $selectedFields);
        });
    }
    
    private function transformPaymentItem($item): array
    {
        // Calcular estadísticas globales del participante para este programa
        $participantStats = $this->calculateParticipantStats($item->participant_id, $item->program_id);
        
        // Calcular el precio real del participante con descuentos aplicados
        $participantPrice = $this->calculateParticipantPrice($item->participant_id, $item->program_id);

        // Separar descuentos normales de liberados
        $discountBreakdown = $this->calculateDiscountBreakdown($item->participant_id, $item->program_id);

        // Construir nombre del participante con CapitalCase
        $participantName = $this->buildParticipantName($item);

        // Obtener información del apoderado (contacto de emergencia)
        $apoderadoInfo = $this->getApoderadoInfo($item->order_id);

        // Determinar el número de documento de la transacción según el tipo
        $transactionDocumentNumber = $this->getTransactionDocumentNumber($item);

        return [
            'id' => $item->payment_id,
            'order_id' => $item->order_id,
            'order_number' => $item->order_number ?? 'N/A',
            // Información para el reporte
            'sales_executive_name' => $this->capitalizeWords($this->cleanUtf8($item->sales_executive_name ?? 'Sin Asignar')),
            'program_code' => $item->program_code ?? 'N/A',
            'program_name' => $this->cleanUtf8($item->program_name ?? 'N/A'),
            'participant_name' => $participantName,
            'payment_form_code' => $item->payment_form_code ?? 'N/A',
            'document_type' => $this->getDocumentTypeLabel($item->document_type),
            'document_type_code' => $item->document_type ?? 'N/A',
            'participant_document' => $this->formatDocument($item->document_number ?? ''),
            'transaction_document_number' => $transactionDocumentNumber, // ← NUEVO CAMPO
            'program_departure_date' => $item->departure_date,
            'program_price' => $participantPrice['final_price'], // Precio real con descuentos
            'scholarships_amount' => (float) $discountBreakdown['normal_discounts'], // Descuentos normales (no liberados)
            'released_amount' => (float) $discountBreakdown['released_amount'], // Monto liberado (100%)
            'paid_installments_display' => $participantStats['paid_installments_display'],
            'total_paid_amount' => (float) ($item->payment_amount ?? 0), // Monto del pago específico
            'overdue_installments_display' => $participantStats['overdue_installments_display'],
            'total_pending_amount' => (float) $participantStats['remaining_balance'],
            // Información adicional para detalles
            'participant_email' => $this->cleanUtf8($item->email ?? ''),
            'participant_document_type' => $item->participant_document_type ?? 'N/A',
            'participant_phone' => $this->cleanUtf8($item->phone ?? ''),
            'program_destination' => $this->cleanUtf8($item->destination ?? ''),
            'sales_executive_email' => $this->cleanUtf8($item->sales_executive_email ?? ''),
            'sales_executive_phone' => $this->cleanUtf8($item->sales_executive_phone ?? ''),
            'financing_type' => $item->payment_type ?? 'N/A',
            'financing_type_label' => $this->getFinancingTypeLabel($item->payment_type ?? ''),
            'payment_amount' => (float) ($item->payment_amount ?? 0),
            'payment_date' => $item->installment_paid_at ?? $item->payment_date,
            'payment_status' => $item->payment_status ?? 'N/A',
            'payment_method_name' => $this->capitalizeWords($this->cleanUtf8($item->payment_gateway_name ?? 'N/A')),
            'payment_method_code' => $item->payment_gateway_code ?? 'N/A',
            'external_contribution' => (float) $discountBreakdown['normal_discounts'],
            'remaining_balance' => (float) $participantStats['remaining_balance'],
            'order_date' => $item->order_date,
            // Datos del pagador (desde orders_detail)
            'payer_name' => $this->capitalizeWords($this->cleanUtf8($item->payer_name ?? '')),
            'payer_email' => $this->cleanUtf8($item->payer_email ?? ''),
            'payer_phone' => $this->cleanUtf8($item->payer_phone ?? ''),
            'payer_document' => $this->formatDocument($item->payer_document ?? ''),
            // Datos de la cuota
            'installment_number' => $item->installment_number ?? 'N/A',
            'installment_amount' => (float) ($item->installment_amount ?? 0),
            'installment_due_date' => $item->installment_due_date,
            'installment_status' => $item->installment_status ?? 'N/A',
            'installment_paid_at' => $item->installment_paid_at,
            // Información del contacto de emergencia (apoderado)
            'emergency_contact_name' => $this->capitalizeWords($this->cleanUtf8($apoderadoInfo['name'])),
            'emergency_contact_phone' => $this->cleanUtf8($apoderadoInfo['phone']),
            'emergency_contact_email' => $this->cleanUtf8($apoderadoInfo['email']),
        ];
    }
    
    private function transformForExportRow($item, array $selectedFields = []): array
    {
        $transformed = $this->transformPaymentItem($item);
        $row = [];
        
        // Construir las columnas en el orden específico solicitado
        $orderedColumns = [
            // 1. Ejecutivo Comercial
            ['section' => 'executive', 'field' => 'name', 'header' => 'Ejecutivo Comercial', 'value' => $transformed['sales_executive_name']],
            // 2. Código (Programa)
            ['section' => 'program', 'field' => 'code', 'header' => 'Código (Programa)', 'value' => $transformed['program_code']],
            // 3. Programa (Nombre Programa)
            ['section' => 'program', 'field' => 'name', 'header' => 'Programa (Nombre Programa)', 'value' => $transformed['program_name']],
            // 4. Nombre del Alumno
            ['section' => 'participant', 'field' => 'name', 'header' => 'Nombre del Alumno', 'value' => $transformed['participant_name']],
            // 5. Forma de Pago
            ['section' => 'payment', 'field' => 'paymentForm', 'header' => 'Forma de Pago', 'value' => $transformed['payment_form_code']],
            // 6. Tipo de Dcto
            ['section' => 'participant', 'field' => 'documentType', 'header' => 'Tipo de Dcto', 'value' => $transformed['document_type_code']],
            // 7. N° Documento
            ['section' => 'participant', 'field' => 'document', 'header' => 'N° Documento', 'value' => $transformed['transaction_document_number']],
            // 8. Fecha de Inicio de Programa
            ['section' => 'program', 'field' => 'startDate', 'header' => 'Fecha de Inicio de Programa', 'value' => $this->formatDate($transformed['program_departure_date'])],
            // 9. $ Programa
            ['section' => 'program', 'field' => 'price', 'header' => '$ Programa', 'value' => round($transformed['program_price'])],
            // 10. Abonos + becas
            ['section' => 'payment', 'field' => 'scholarships', 'header' => 'Abonos + becas', 'value' => round($transformed['scholarships_amount'])],
            // 11. Valor alumno liberado
            ['section' => 'payment', 'field' => 'releasedAmount', 'header' => 'Valor alumno liberado', 'value' => round($transformed['released_amount'])],
            // 12. N° Cuotas Pagadas
            ['section' => 'payment', 'field' => 'paidInstallments', 'header' => 'N° Cuotas Pagadas', 'value' => $transformed['paid_installments_display']],
            // 13. Monto Total Pagado
            ['section' => 'payment', 'field' => 'totalPaid', 'header' => 'Monto Total Pagado', 'value' => round($transformed['total_paid_amount'])],
            // 14. N° Cuotas No Pagadas
            ['section' => 'payment', 'field' => 'unpaidInstallments', 'header' => 'N° Cuotas No Pagadas', 'value' => $transformed['overdue_installments_display']],
            // 15. Monto Total por Cobrar
            ['section' => 'payment', 'field' => 'pendingAmount', 'header' => 'Monto Total por Cobrar', 'value' => round($transformed['total_pending_amount'])],
        ];
        
        // Agregar solo las columnas seleccionadas en el orden correcto
        foreach ($orderedColumns as $column) {
            if (isset($selectedFields[$column['section']]) && 
                in_array($column['field'], $selectedFields[$column['section']])) {
                $row[$column['header']] = $column['value'];
            }
        }
        
        // Si no hay campos seleccionados, incluir las columnas del reporte solicitado
        if (empty($selectedFields)) {
            $row = [
                'Ejecutivo Comercial' => $transformed['sales_executive_name'],
                'Codigo (Programa)' => $transformed['program_code'],
                'Programa (Nombre Programa)' => $transformed['program_name'],
                'Nombre del Alumno' => $transformed['participant_name'],
                'Forma de Pago' => $transformed['payment_form_code'],
                'Tipo de Dcto' => $transformed['document_type_code'],
                'N° Documento' => $transformed['transaction_document_number'],
                                'Fecha de Inicio de Programa' => $this->formatDate($transformed['program_departure_date']),
                '$ Programa' => round($transformed['program_price']),
                'Abonos + becas' => round($transformed['scholarships_amount']),
                'Valor alumno liberado' => round($transformed['released_amount']),
                'N° Cuotas Pagadas' => $transformed['paid_installments_display'],
                'Monto Total Pagado' => round($transformed['total_paid_amount']),
                'N° Cuotas No Pagadas' => $transformed['overdue_installments_display'],
                'Monto Total por Cobrar' => round($transformed['total_pending_amount']),
            ];
        }
        
        return $row;
    }
    
    /*
    private function calculateReleasedAmount($orderId): float
    {
        // Buscar movimientos con tipo "LIBERADO" para esta orden
        // Tabla order_movements no existe en la base de datos
        $releasedAmount = DB::table('order_movements')
            ->where('order_id', $orderId)
            ->where('type', 'LIBERADO')
            ->sum('amount');
            
        return (float) $releasedAmount;
    }
    
    private function calculateExternalContribution($orderId): float
    {
        // Buscar aportes externos para esta orden
        // Tabla external_contributions no existe en la base de datos
        $externalContribution = DB::table('external_contributions')
            ->where('order_id', $orderId)
            ->sum('amount');
            
        return (float) $externalContribution;
    }
    */
    
    private function getFinancingTypeLabel(string $financingType): string
    {
        $labels = [
            'total' => 'Pago Único',
            'monthly' => 'Pago en Cuotas',
        ];
        
        return $labels[$financingType] ?? $financingType;
    }
    
    private function formatDate($date): string
    {
        if (!$date) return 'N/A';
        return \Carbon\Carbon::parse($date)->format('d/m/Y');
    }
    
    private function cleanUtf8(string $text): string
    {
        if (!$text) return '';
        
        // Detectar encoding
        $encoding = mb_detect_encoding($text, ['UTF-8', 'ISO-8859-1', 'ASCII'], true);
        
        if ($encoding === false) {
            $encoding = 'ISO-8859-1';
        }
        
        // Convertir a UTF-8 si es necesario
        if ($encoding !== 'UTF-8') {
            $text = mb_convert_encoding($text, 'UTF-8', $encoding);
        }
        
        return $text;
    }

    private function buildParticipantName($item): string
    {
        $firstName = $this->cleanUtf8($item->first_name ?? '');
        $secondName = $this->cleanUtf8($item->second_name ?? '');
        $lastName = $this->cleanUtf8($item->first_last_name ?? '');
        $secondLastName = $this->cleanUtf8($item->second_last_name ?? '');

        $nameParts = [];
        if ($firstName) {
            $nameParts[] = $this->capitalizeWords($firstName);
        }
        if ($secondName) {
            $nameParts[] = $this->capitalizeWords($secondName);
        }
        if ($lastName) {
            $nameParts[] = $this->capitalizeWords($lastName);
        }
        if ($secondLastName) {
            $nameParts[] = $this->capitalizeWords($secondLastName);
        }

        return implode(' ', $nameParts);
    }

    /**
     * Aplicar CapitalCase a un string
     */
    private function capitalizeWords(string $text): string
    {
        return ucwords(strtolower(trim($text)));
    }

    private function getApoderadoInfo($orderId): array
    {
        // Obtener el participant_id desde la orden
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) return ['name' => 'N/A', 'phone' => 'N/A', 'email' => 'N/A'];
        
        // Buscar el contacto de emergencia para este participante
        $emergencyContact = DB::table('emergency_contact')
            ->where('participant_id', $order->participant_id)
            ->first();

        return $emergencyContact ? [
            'name' => $this->capitalizeWords($this->cleanUtf8($emergencyContact->name)),
            'phone' => $this->cleanUtf8($emergencyContact->phone),
            'email' => $this->cleanUtf8($emergencyContact->email)
        ] : ['name' => 'N/A', 'phone' => 'N/A', 'email' => 'N/A'];
    }

    /**
     * Formatea el número de documento según su tipo
     */
    private function formatDocument($documentNumber): string
    {
        if (empty($documentNumber)) {
            return 'N/A';
        }

        // Detectar automáticamente si es RUT por formato
        $cleanNumber = str_replace(['.', '-'], '', $documentNumber);
        if (preg_match('/^\d{7,8}[\dK]$/', $cleanNumber)) {
            // Es un RUT, formatear como RUT
            $body = substr($cleanNumber, 0, -1);
            $dv = substr($cleanNumber, -1);
            $withDots = number_format($body, 0, '', '.');
            return $withDots . '-' . strtoupper($dv);
        } else {
            // Es un pasaporte u otro documento, mostrar tal como está
            return $this->cleanUtf8($documentNumber);
        }
    }

    /**
     * Obtener el label del tipo de documento
     */
    private function getDocumentTypeLabel($documentType): string
    {
        $labels = [
            'B2' => 'Boleta',
            'BC' => 'Nota de Crédito',
            'FF' => 'Factura',
            'AC' => 'Reserva',
        ];

        return $labels[$documentType] ?? ($documentType ?: 'N/A');
    }

    /**
     * Obtener cantidad de cuotas pagadas para una orden
     */
    private function getPaidInstallmentsCount($orderId): int
    {
        return DB::table('orders_detail')
            ->where('order_id', $orderId)
            ->where('is_paid', true)
            ->count();
    }

    /**
     * Obtener cantidad de cuotas pendientes para una orden
     */
    private function getPendingInstallmentsCount($orderId): int
    {
        return DB::table('orders_detail')
            ->where('order_id', $orderId)
            ->where('is_paid', false)
            ->count();
    }

    /**
     * Calcular estadísticas completas de la orden
     */
    private function calculateParticipantStats($participantId, $programId): array
    {
        // Calcular el precio real con descuentos aplicados
        $participantPrice = $this->calculateParticipantPrice($participantId, $programId);
        $finalPriceWithDiscounts = $participantPrice['final_price'];

        // CALCULAR EL MONTO REAL PAGADO desde la tabla payments (incluye reembolsos)
        $totalPaidAmount = DB::table('payments')
            ->join('orders', 'payments.order_id', '=', 'orders.id')
            ->where('orders.participant_id', $participantId)
            ->where('orders.program_id', $programId)
            ->whereIn('payments.status', ['completed', 'approved'])
            ->sum('payments.amount');

        // Buscar TODOS los planes de cuotas del participante para este programa
        $installmentPlans = DB::table('installment_plans')
            ->where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->get();

        if ($installmentPlans->count() > 0) {
            // Usar el sistema de cuotas (installments) - SUMAR TODOS LOS PLANES
            $planIds = $installmentPlans->pluck('id')->toArray();
            
            $installmentStats = DB::table('installments')
                ->whereIn('installment_plan_id', $planIds)
                ->selectRaw('
                    COUNT(*) as total_installments,
                    COUNT(CASE WHEN status = "paid" THEN 1 END) as paid_installments,
                    COUNT(CASE WHEN status = "overdue" THEN 1 END) as overdue_installments
                ')
                ->first();

            $totalInstallments = $installmentStats->total_installments ?? 0;
            $paidInstallments = $installmentStats->paid_installments ?? 0;
            $overdueInstallments = $installmentStats->overdue_installments ?? 0;

            // Calcular el saldo pendiente real
            $remainingBalance = max($finalPriceWithDiscounts - $totalPaidAmount, 0);

            return [
                'total_paid' => $totalPaidAmount,
                'remaining_balance' => $remainingBalance,
                'paid_installments_display' => $totalInstallments > 0 ? "{$paidInstallments}/{$totalInstallments}" : '0',
                'overdue_installments_display' => $overdueInstallments > 0 ? "{$overdueInstallments}" : '0',
            ];
        } else {
            // Usar el sistema de orders_detail (pagos presenciales/totales)
            $allOrders = DB::table('orders')
                ->where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->pluck('id')
                ->toArray();

            $orderDetailStats = DB::table('orders_detail')
                ->whereIn('order_id', $allOrders)
                ->selectRaw('
                    COUNT(*) as total_installments,
                    COUNT(CASE WHEN is_paid = 1 THEN 1 END) as paid_installments,
                    COUNT(CASE WHEN is_paid = 0 AND due_date < CURDATE() THEN 1 END) as overdue_installments
                ')
                ->first();

            $totalInstallments = $orderDetailStats->total_installments ?? 0;
            $paidInstallments = $orderDetailStats->paid_installments ?? 0;
            $overdueInstallments = $orderDetailStats->overdue_installments ?? 0;

            // Calcular el saldo pendiente real
            $remainingBalance = max($finalPriceWithDiscounts - $totalPaidAmount, 0);

            return [
                'total_paid' => $totalPaidAmount,
                'remaining_balance' => $remainingBalance,
                'paid_installments_display' => $totalInstallments > 0 ? "{$paidInstallments}/{$totalInstallments}" : '0',
                'overdue_installments_display' => $overdueInstallments > 0 ? "{$overdueInstallments}" : '0',
            ];
        }
    }

    /**
     * Calcula el precio real del participante para un programa específico usando ParticipantPriceHelper
     */
    private function calculateParticipantPrice(int $participantId, int $programId): array
    {
        try {
            $participant = \App\Models\Participant::find($participantId);
            $program = \App\Models\Program::find($programId);
            
            if (!$participant || !$program) {
                return [
                    'base_price' => 0,
                    'adjustments' => 0,
                    'discounts' => 0,
                    'final_price' => 0
                ];
            }
            
            return \App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
        } catch (\Exception $e) {
            return [
                'base_price' => 0,
                'adjustments' => 0,
                'discounts' => 0,
                'final_price' => 0
            ];
        }
    }

    /**
     * Calcula el desglose de descuentos separando normales de liberados
     */
    private function calculateDiscountBreakdown(int $participantId, int $programId): array
    {
        try {
            // Buscar el participant_program_id
            $pp = DB::table('participant_program')
                ->where('participant_id', $participantId)
                ->where('program_id', $programId)
                ->first();
                
            if (!$pp) {
                return [
                    'normal_discounts' => 0,
                    'released_amount' => 0
                ];
            }
            
            // Obtener todos los descuentos activos
            $discounts = DB::table('participant_program_discounts')
                ->where('participant_program_id', $pp->id)
                ->get();
                
            $normalDiscounts = 0;
            $releasedAmount = 0;
            
            foreach ($discounts as $discount) {
                if ($discount->discount_type === 'released') {
                    // Es un descuento liberado
                    if ($discount->percent == 100) {
                        // Calcular el monto liberado basado en el precio base
                        $basePrice = $this->getBasePrice($participantId, $programId);
                        $releasedAmount += $basePrice;
                    } else {
                        $releasedAmount += $discount->amount ?? 0;
                    }
                } else {
                    // Es un descuento normal (scholarship)
                    if ($discount->percent && $discount->percent > 0) {
                        // Calcular el monto del descuento porcentual
                        $basePrice = $this->getBasePrice($participantId, $programId);
                        $normalDiscounts += ($basePrice * $discount->percent) / 100;
                    }
                    if ($discount->amount && $discount->amount > 0) {
                        $normalDiscounts += $discount->amount;
                    }
                }
            }
            
            return [
                'normal_discounts' => $normalDiscounts,
                'released_amount' => $releasedAmount
            ];
        } catch (\Exception $e) {
            return [
                'normal_discounts' => 0,
                'released_amount' => 0
            ];
        }
    }

    /**
     * Obtiene el precio base para calcular descuentos porcentuales
     */
    private function getBasePrice(int $participantId, int $programId): float
    {
        // Buscar el precio individual del participante para este programa
        $pp = DB::table('participant_program')
            ->where('participant_id', $participantId)
            ->where('program_id', $programId)
            ->first();
            
        if ($pp && $pp->individual_price) {
            return (float) $pp->individual_price;
        }
        
        // Fallback al precio del programa
        $programPrice = DB::table('programs')
            ->where('id', $programId)
            ->value('trip_price');
            
        return (float) ($programPrice ?? 0);
    }

    /**
     * Determina el número de documento de la transacción según el tipo de pago
     */
    private function getTransactionDocumentNumber($item): string
    {
        // 1. Para pagos presenciales y devoluciones: usar payment_code
        if ($item->payment_code) {
            return $item->payment_code;
        }
        
        // 2. Para facturas y boletas: usar bsale_number
        if ($item->bsale_number) {
            return $item->bsale_number;
        }
        
        // 3. Para reservas (AC): generar folio del contrato (código_programa-guión-documento_participante)
        if ($item->document_type === 'AC') {
            $programCode = $item->program_code ?? 'N/A';
            // Para RUTs, eliminar puntos y guiones del documento del participante
            $participantDoc = $this->cleanDocumentNumber($item->document_number ?? '');
            return $programCode . '-' . $participantDoc;
        }
        
        // 4. Para otros casos: usar buy_order o número de orden
        if ($item->buy_order) {
            return $item->buy_order;
        }
        
        if ($item->order_number) {
            return $item->order_number;
        }
        
        return 'N/A';
    }

    /**
     * Limpia el número de documento eliminando puntos y guiones (para RUTs)
     */
    private function cleanDocumentNumber(?string $documentNumber): string
    {
        if (empty($documentNumber)) {
            return 'N/A';
        }
        
        // Eliminar puntos y guiones para RUTs
        $cleanNumber = str_replace(['.', '-'], '', $documentNumber);
        
        // Si es un RUT válido (7-8 dígitos + dígito verificador), devolver limpio
        if (preg_match('/^\d{7,8}[\dK]$/', $cleanNumber)) {
            return $cleanNumber;
        }
        
        // Si no es un RUT, devolver tal como está
        return $documentNumber;
    }
}
