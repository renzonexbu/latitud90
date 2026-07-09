<?php

namespace App\Services\Admin\Reports\Executives;

use App\Models\Payment;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ExecutivesConsolidatedService
{
    /**
     * Calcula el saldo progresivo (running balance) para cada pago de un conjunto de participante+programa.
     * Retorna un mapa: payment_id → saldo después de ese pago.
     *
     * El saldo parte del precio final (ParticipantPriceHelper) y se va descontando
     * cronológicamente con cada pago/devolución.
     */
    private function calculateRunningBalances(Collection $payments): array
    {
        $balanceMap = [];

        // Agrupar pagos por participante+programa
        $groups = $payments->groupBy(function ($payment) {
            $participantId = $payment->order?->participant_id ?? 0;
            $programId = $payment->order?->program_id ?? 0;
            return "{$participantId}-{$programId}";
        });

        foreach ($groups as $key => $groupPayments) {
            [$participantId, $programId] = explode('-', $key);

            if (!$participantId || !$programId) continue;

            // Usar servicio centralizado para obtener datos financieros
            $financialData = \App\Services\Admin\ParticipantFinancialService::calculate(
                (int) $participantId,
                (int) $programId
            );

            $isDeBaja = $financialData['is_de_baja'];
            $price = $financialData['net_amount'];

            // Obtener TODOS los pagos de este participante+programa en orden cronológico
            $allPayments = Payment::whereHas('order', function ($q) use ($participantId, $programId) {
                    $q->where('participant_id', $participantId)
                      ->where('program_id', $programId);
                })
                ->whereIn('status', ['approved', 'completed'])
                ->orderByRaw('CASE WHEN payment_source = "subscription" THEN created_at ELSE COALESCE(transaction_date, created_at) END ASC')
                ->orderBy('id', 'asc')
                ->get(['id', 'amount']);

            // Calcular saldo progresivo — misma convención que el Estado de Cuenta Parcial:
            //   Positivo = excedente a favor del pagador (pagó de más, o el programa bajó de precio)
            //   Negativo = saldo deudor (aún debe pagar)
            //   Cero     = pago exacto del precio actual del programa
            $accumulatedPaid = 0;
            foreach ($allPayments as $p) {
                $accumulatedPaid += $p->amount;
                // Si está de baja, saldo queda en $0 (ya no debe nada)
                $balanceMap[$p->id] = $isDeBaja ? 0 : round($accumulatedPaid - $price, 0);
            }
        }

        return $balanceMap;
    }

    /**
     * Normaliza un documento removiendo puntos, guiones y espacios
     */
    private function normalizeDocument(?string $document): string
    {
        if (!$document) {
            return '';
        }
        return preg_replace('/[.\-\s]/', '', $document);
    }

    /**
     * Retorna datos para Consolidado de Área Ingresos (apoderados)
     */
    public function getConsolidated(array $filters): array
    {
        $page = (int)($filters['page'] ?? 1);
        $perPage = 25;

        // Consulta base: filas por pago (ingresos y devoluciones)
        // Incluir 'approved' (pagos offline/manuales) y 'completed' (pasarelas de pago)
        $query = Payment::with([
            'paymentOption',
            'order.participant',
            'order.programCourse.salesExecutive',
            'order.orderDetails',
            'order.participantProgram',
            'order.participantProgram.discounts',
            'order.payments'
        ])->whereIn('payments.status', ['approved', 'completed']);

        // Aplicar filtros (para suscripciones usar created_at en vez de transaction_date)
        if (!empty($filters['dateFrom'])) {
            $query->whereRaw('DATE(CASE WHEN payments.payment_source = "subscription" THEN payments.created_at ELSE COALESCE(payments.transaction_date, payments.created_at) END) >= ?', [$filters['dateFrom']]);
        }
        if (!empty($filters['dateTo'])) {
            $query->whereRaw('DATE(CASE WHEN payments.payment_source = "subscription" THEN payments.created_at ELSE COALESCE(payments.transaction_date, payments.created_at) END) <= ?', [$filters['dateTo']]);
        }
        if (!empty($filters['programId'])) {
            $query->whereHas('order', function ($q) use ($filters) {
                $q->where('program_id', $filters['programId']);
            });
        }
        if (!empty($filters['salesExecutiveId'])) {
            $query->whereHas('order.programCourse', function ($q) use ($filters) {
                $q->where('sales_executive_id', $filters['salesExecutiveId']);
            });
        }
        // Filtro por documento/RUT del participante
        if (!empty($filters['documentSearch'])) {
            $documentSearch = $this->normalizeDocument($filters['documentSearch']);
            $query->whereHas('order.participant', function ($q) use ($documentSearch) {
                // Buscar normalizando el documento en BD (sin puntos ni guiones)
                $q->whereRaw("REPLACE(REPLACE(document_number, '.', ''), '-', '') LIKE ?", ["%{$documentSearch}%"]);
            });
        }
        // Filtro por estado activo/inactivo del participante en el programa
        if (!empty($filters['status'])) {
            $isActive = $filters['status'] === 'active';
            $query->whereHas('order.participantProgram', function ($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }

        // Log de la consulta SQL
        \Illuminate\Support\Facades\Log::info('Consulta SQL generada', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $filters
        ]);

        // Obtener pagos con paginación, ordenados por fecha de transacción (más reciente primero)
        $payments = $query
            ->orderByRaw('CASE WHEN payments.payment_source = "subscription" THEN payments.created_at ELSE COALESCE(payments.transaction_date, payments.created_at) END DESC')
            ->orderBy('payments.id', 'desc')
            ->select('payments.*')
            ->paginate($perPage, ['*'], 'page', $page);

        // Log de resultados
        \Illuminate\Support\Facades\Log::info('Resultados de la consulta', [
            'total_payments' => $payments->total(),
            'current_page' => $payments->currentPage(),
            'per_page' => $payments->perPage(),
            'items_count' => count($payments->items())
        ]);

        // Log de items antes de transformar
        \Illuminate\Support\Facades\Log::info('Items antes de transformar', [
            'payment_count' => count($payments->items()),
            'first_payment' => $payments->items()[0] ?? 'no hay pagos'
        ]);

        // Calcular saldos progresivos para los pagos de esta página
        $pagePayments = collect($payments->items());
        $balanceMap = $this->calculateRunningBalances($pagePayments);

        // Transformar datos para la tabla (fila por pago)
        $items = $pagePayments->map(function ($payment) use ($balanceMap) {
            $order = $payment->order;
            $participant = $order?->participant;
            $program = $order?->programCourse;

            // Buscar participant_program directamente (la order de NC puede no tener participant_program_id)
            $participantProgram = ($participant && $program)
                ? \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->first()
                : null;

            // Saldo progresivo: refleja el impacto de cada pago/devolución
            $saldo = $balanceMap[$payment->id] ?? 0;

            // Monto liberado (descuento tipo 'released'): puede venir por porcentaje, monto o ambos.
            // Si solo se lee 'amount', se pierden los descuentos definidos por porcentaje (caso común).
            $liberatedAmount = 0;
            if ($participantProgram && $program) {
                $basePriceForLiberated = $participant
                    ? (float) (\App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program)['base_price'] ?? 0)
                    : 0.0;

                foreach ($participantProgram->discounts->where('discount_type', 'released') as $rd) {
                    if ($rd->percent && $rd->percent > 0) {
                        $liberatedAmount += ($basePriceForLiberated * $rd->percent) / 100;
                    }
                    if ($rd->amount && $rd->amount > 0) {
                        $liberatedAmount += (float) $rd->amount;
                    }
                }
                $liberatedAmount = round($liberatedAmount, 2);
            }

            // Contacto pagador: para reembolsos, buscar en la orden del pago original
            $payerContactRaw = null;
            $payerEmail = null;
            if ($payment->amount < 0 && $participant && $program) {
                $originalPaymentOrder = \App\Models\Order::where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->whereHas('payments', fn($q) => $q->where('amount', '>', 0))
                    ->with('orderDetails')
                    ->latest()
                    ->first();
                $originalDetail = $originalPaymentOrder?->orderDetails?->first();
                $payerContactRaw = $originalDetail?->name;
                $payerEmail = $originalDetail?->email;
            }
            if (!$payerContactRaw) {
                $firstDetail = $order?->orderDetails?->first();
                $payerContactRaw = $firstDetail?->name ?? ($participant?->full_name ?? null);
                $payerEmail = $payerEmail ?: ($firstDetail?->email ?? ($participant?->email ?? null));
            }
            $payerContact = $payerContactRaw ? ucwords(strtolower($payerContactRaw)) : null;

            // Construir nombre del participante en formato: "Apellido1 Apellido2 Nombre1 Nombre2"
            $participantFullName = 'N/A';
            if ($participant) {
                $participantFullName = trim(implode(' ', array_filter([
                    $participant->first_last_name,
                    $participant->second_last_name,
                    $participant->first_name,
                    $participant->second_name
                ]))) ?: 'N/A';
                $participantFullName = ucwords(strtolower($participantFullName));
            }

            return [
                'id' => $payment->id,
                'program_number' => $program?->code ?? 'N/A',
                'identification_number' => $participant?->document_number ?? 'N/A',
                'full_name' => $participantFullName,
                'status' => $participantProgram?->is_active ? 'Activo' : 'Baja',
                'payment_or_refund' => $payment->amount ?? 0,
                'document_number' => $payment->bsale_number ?: $payment->payment_code ?: $order?->order_number ?: 'N/A',
                'document_type' => $payment->document_type ?: 'N/A',
                'payment_form' => $payment->paymentOption?->report_code ?: 'N/A',
                'payment_date' => ($payment->payment_source === 'subscription' ? ($payment->created_at ? Carbon::parse($payment->created_at)->format('d/m/Y') : 'N/A') : ($payment->transaction_date ? Carbon::parse($payment->transaction_date)->format('d/m/Y') : 'N/A')),
                'payer_contact' => $payerContact ?: 'N/A',
                'payer_email' => $payerEmail ?: 'N/A',
                'liberated' => $liberatedAmount,
                'saldo' => $saldo,
                'sales_executive_name' => $program?->salesExecutive?->name ?: 'N/A',
                'amount' => $payment->amount ?? 0,
                'participant_id' => $participant?->id,
            ];
        });

        // Crear paginador con datos transformados
        $paginator = new LengthAwarePaginator(
            $items,
            $payments->total(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Calcular resumen
        $summary = [
            'totalParticipants' => collect($items)->pluck('participant_id')->filter()->unique()->count(),
            'totalPayments' => $payments->total(),
            'totalAmount' => collect($items)->sum('amount'),
            'totalLiberated' => collect($items)->sum('liberated'),
        ];

        return [
            'consolidated' => $paginator,
            'filters' => [
                'dateFrom' => $filters['dateFrom'] ?? '',  // Vacío para mostrar todo el histórico
                'dateTo' => $filters['dateTo'] ?? Carbon::now('America/Santiago')->format('Y-m-d'),
                'programId' => $filters['programId'] ?? "",
                'salesExecutiveId' => $filters['salesExecutiveId'] ?? null,
                'documentSearch' => $filters['documentSearch'] ?? "",
                'status' => $filters['status'] ?? "",
            ],
            'summary' => $summary,
            'programs' => \App\Models\ProgramCourse::select('id', 'code', 'name')->where('active', true)->with('program:id,destination')->orderBy('code')->get(),
            'salesExecutives' => \App\Models\SalesExecutive::select('id', 'name')->where('active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * Retorna datos para exportación sin paginación
     */
    public function getConsolidatedForExport(array $filters): array
    {
        // Normalizar filtros: evitar que el string 'null' o vacío aplique filtros
        $normalizedFilters = $filters;
        foreach (['programId', 'salesExecutiveId', 'documentSearch', 'status'] as $key) {
            if (isset($normalizedFilters[$key]) && ($normalizedFilters[$key] === 'null' || $normalizedFilters[$key] === '')) {
                $normalizedFilters[$key] = null;
            }
        }

        // Consulta base: filas por pago (ingresos y devoluciones) - SIN PAGINACIÓN
        // Incluir 'approved' (pagos offline/manuales) y 'completed' (pasarelas de pago)
        $query = Payment::with([
            'paymentOption',
            'paymentGateway',
            'order.participant',
            'order.programCourse.salesExecutive',
            'order.orderDetails',
            'order.participantProgram',
            'order.participantProgram.discounts',
            'order.payments'
        ])->whereIn('payments.status', ['approved', 'completed']);

        // Aplicar filtros (para suscripciones usar created_at en vez de transaction_date)
        if (!empty($normalizedFilters['dateFrom'])) {
            $query->whereRaw('DATE(CASE WHEN payments.payment_source = "subscription" THEN payments.created_at ELSE COALESCE(payments.transaction_date, payments.created_at) END) >= ?', [$normalizedFilters['dateFrom']]);
        }
        if (!empty($normalizedFilters['dateTo'])) {
            $query->whereRaw('DATE(CASE WHEN payments.payment_source = "subscription" THEN payments.created_at ELSE COALESCE(payments.transaction_date, payments.created_at) END) <= ?', [$normalizedFilters['dateTo']]);
        }
        if (!empty($normalizedFilters['programId'])) {
            $query->whereHas('order', function ($q) use ($normalizedFilters) {
                $q->where('program_id', $normalizedFilters['programId']);
            });
        }
        if (!empty($normalizedFilters['salesExecutiveId'])) {
            $query->whereHas('order.programCourse', function ($q) use ($normalizedFilters) {
                $q->where('sales_executive_id', $normalizedFilters['salesExecutiveId']);
            });
        }
        // Filtro por documento/RUT del participante
        if (!empty($normalizedFilters['documentSearch'])) {
            $documentSearch = $this->normalizeDocument($normalizedFilters['documentSearch']);
            $query->whereHas('order.participant', function ($q) use ($documentSearch) {
                $q->whereRaw("REPLACE(REPLACE(document_number, '.', ''), '-', '') LIKE ?", ["%{$documentSearch}%"]);
            });
        }
        // Filtro por estado activo/inactivo del participante en el programa
        if (!empty($normalizedFilters['status'])) {
            $isActive = $normalizedFilters['status'] === 'active';
            $query->whereHas('order.participantProgram', function ($q) use ($isActive) {
                $q->where('is_active', $isActive);
            });
        }

        // Log de la consulta SQL para exportación
        \Illuminate\Support\Facades\Log::info('Consulta SQL para exportación', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $normalizedFilters
        ]);

        // Obtener TODOS los pagos sin paginación, ordenados por nombre del participante y fecha de pago
        $payments = $query
            ->leftJoin('orders as o_sort', 'payments.order_id', '=', 'o_sort.id')
            ->leftJoin('participants as p_sort', 'o_sort.participant_id', '=', 'p_sort.id')
            ->orderBy('p_sort.first_last_name', 'asc')
            ->orderBy('p_sort.second_last_name', 'asc')
            ->orderBy('p_sort.first_name', 'asc')
            ->orderBy('p_sort.second_name', 'asc')
            ->orderByRaw('CASE WHEN payments.payment_source = "subscription" THEN payments.created_at ELSE COALESCE(payments.transaction_date, payments.created_at) END ASC')
            ->select('payments.*')
            ->get();

        // Log de resultados para exportación
        \Illuminate\Support\Facades\Log::info('Datos para exportación', [
            'total_payments' => $payments->count(),
            'filters' => $normalizedFilters,
            'first_payment' => $payments->first() ? 'existe' : 'no hay pagos'
        ]);

        // Calcular saldos progresivos para todos los pagos
        $balanceMap = $this->calculateRunningBalances($payments);

        // Transformar datos para exportación (fila por pago)
        $items = $payments->map(function ($payment) use ($balanceMap) {
            $order = $payment->order;
            $participant = $order?->participant;
            $program = $order?->programCourse;

            // Buscar participant_program directamente (la order de NC puede no tener participant_program_id)
            $participantProgram = ($participant && $program)
                ? \App\Models\ParticipantProgram::where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->first()
                : null;

            // Saldo progresivo: refleja el impacto de cada pago/devolución
            $saldo = $balanceMap[$payment->id] ?? 0;

            // Monto liberado (descuento tipo 'released'): puede venir por porcentaje, monto o ambos.
            // Si solo se lee 'amount', se pierden los descuentos definidos por porcentaje (caso común).
            $liberatedAmount = 0;
            if ($participantProgram && $program) {
                $basePriceForLiberated = $participant
                    ? (float) (\App\Helpers\ParticipantPriceHelper::calculateParticipantPrice($participant, $program)['base_price'] ?? 0)
                    : 0.0;

                foreach ($participantProgram->discounts->where('discount_type', 'released') as $rd) {
                    if ($rd->percent && $rd->percent > 0) {
                        $liberatedAmount += ($basePriceForLiberated * $rd->percent) / 100;
                    }
                    if ($rd->amount && $rd->amount > 0) {
                        $liberatedAmount += (float) $rd->amount;
                    }
                }
                $liberatedAmount = round($liberatedAmount, 2);
            }

            // Contacto pagador: para reembolsos, buscar en la orden del pago original
            $payerContactRaw = null;
            $payerEmail = null;
            if ($payment->amount < 0 && $participant && $program) {
                $originalPaymentOrder = \App\Models\Order::where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->whereHas('payments', fn($q) => $q->where('amount', '>', 0))
                    ->with('orderDetails')
                    ->latest()
                    ->first();
                $originalDetail = $originalPaymentOrder?->orderDetails?->first();
                $payerContactRaw = $originalDetail?->name;
                $payerEmail = $originalDetail?->email;
            }
            if (!$payerContactRaw) {
                $firstDetail = $order?->orderDetails?->first();
                $payerContactRaw = $firstDetail?->name ?? ($participant?->full_name ?? null);
                $payerEmail = $payerEmail ?: ($firstDetail?->email ?? ($participant?->email ?? null));
            }
            $payerContact = $payerContactRaw ? ucwords(strtolower($payerContactRaw)) : null;

            // Construir nombre del participante en formato: "Apellido1 Apellido2 Nombre1 Nombre2"
            $participantFullName = 'N/A';
            if ($participant) {
                $participantFullName = trim(implode(' ', array_filter([
                    $participant->first_last_name,
                    $participant->second_last_name,
                    $participant->first_name,
                    $participant->second_name
                ]))) ?: 'N/A';
                $participantFullName = ucwords(strtolower($participantFullName));
            }

            // NRO AUTORIZACIÓN:
            // - Presencial → authorization_code ingresado en la ficha
            // - Resto de pasarelas → primeros 8 chars de order.uuid del gateway_response
            $authorizationNumber = 'N/A';
            if (($payment->paymentGateway?->code ?? '') === 'presencial') {
                $authorizationNumber = $payment->authorization_code ?: 'N/A';
            } elseif (!empty($payment->gateway_response)) {
                $gatewayData = is_string($payment->gateway_response)
                    ? json_decode($payment->gateway_response, true)
                    : (array) $payment->gateway_response;
                $uuid = $gatewayData['full_response']['payment']['order']['uuid']
                    ?? $gatewayData['payment']['order']['uuid']
                    ?? null;
                if ($uuid) {
                    $authorizationNumber = substr($uuid, 0, 8);
                }
            }

            return [
                'id' => $payment->id,
                'program_number' => $program?->code ?? 'N/A',
                'authorization_number' => $authorizationNumber,
                'identification_number' => $participant?->document_number ?? 'N/A',
                'full_name' => $participantFullName,
                'status' => $participantProgram?->is_active ? 'Activo' : 'Baja',
                'payment_or_refund' => $payment->amount ?? 0,
                'document_number' => $payment->bsale_number ?: $payment->payment_code ?: $order?->order_number ?: 'N/A',
                'document_type' => $payment->document_type ?: 'N/A',
                'payment_form' => $payment->paymentOption?->report_code ?: 'N/A',
                'installments_number' => max(1, (int) ($payment->installments_number ?? 1)),
                'payment_date' => ($payment->payment_source === 'subscription' ? ($payment->created_at ? Carbon::parse($payment->created_at)->format('d/m/Y') : 'N/A') : ($payment->transaction_date ? Carbon::parse($payment->transaction_date)->format('d/m/Y') : 'N/A')),
                'payer_contact' => $payerContact ?: 'N/A',
                'payer_email' => $payerEmail ?: 'N/A',
                'liberated' => $liberatedAmount,
                'saldo' => $saldo,
                'sales_executive_name' => $program?->salesExecutive?->name ?: 'N/A',
                'amount' => $payment->amount ?? 0,
                'participant_id' => $participant?->id,
            ];
        });

        // Log de items transformados
        \Illuminate\Support\Facades\Log::info('Items transformados para exportación', [
            'items_count' => $items->count(),
            'first_item' => $items->first() ? 'existe' : 'no hay items'
        ]);

        // Calcular resumen para exportación
        $summary = [
            'totalParticipants' => $items->pluck('participant_id')->filter()->unique()->count(),
            'totalPayments' => $items->count(),
            'totalAmount' => $items->sum('amount'),
            'totalLiberated' => $items->sum('liberated'),
        ];

        return [
            'items' => $items,
            'summary' => $summary,
        ];
    }
}


