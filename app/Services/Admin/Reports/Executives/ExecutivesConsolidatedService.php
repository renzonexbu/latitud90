<?php

namespace App\Services\Admin\Reports\Executives;

use App\Models\Payment;
use App\Helpers\ParticipantPriceHelper;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExecutivesConsolidatedService
{
    /**
     * Calcula el saldo de un participante en un programa.
     * Replica la lógica de GetParticipantsService: precio final - (pagos normales + cuotas suscripción).
     */
    private function calculateParticipantSaldo($participant, $programCourse): int
    {
        if (!$participant || !$programCourse) {
            return 0;
        }

        // Precio total a pagar (usando ParticipantPriceHelper, igual que vista de participantes)
        $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $programCourse);
        $totalDue = $priceData['final_price'] ?? 0;

        // Pagos normales (excluyendo los de suscripción, que se cuentan aparte en installments)
        $normalPayments = (float) Payment::whereHas('order', function ($q) use ($participant, $programCourse) {
                $q->where('participant_id', $participant->id)
                  ->where('program_id', $programCourse->id);
            })
            ->whereIn('status', ['approved', 'completed'])
            ->where(function ($query) {
                $query->whereNull('payment_source')
                      ->orWhere('payment_source', '!=', 'subscription');
            })
            ->sum('amount');

        // Cuotas de suscripción pagadas (desde tabla installments)
        $subscriptionPayments = (float) DB::table('installments')
            ->join('installment_plans', 'installments.installment_plan_id', '=', 'installment_plans.id')
            ->where('installment_plans.participant_id', $participant->id)
            ->where('installment_plans.program_id', $programCourse->id)
            ->where('installments.status', 'paid')
            ->sum('installments.amount');

        $totalPaid = $normalPayments + $subscriptionPayments;

        return max(0, round($totalDue - $totalPaid, 0));
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

        // Aplicar filtros
        if (!empty($filters['dateFrom'])) {
            $query->whereDate('payments.transaction_date', '>=', $filters['dateFrom']);
        }
        if (!empty($filters['dateTo'])) {
            $query->whereDate('payments.transaction_date', '<=', $filters['dateTo']);
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
        // Filtro por estado activo/inactivo del participante
        if (!empty($filters['status'])) {
            $isActive = $filters['status'] === 'active';
            $query->whereHas('order.participant', function ($q) use ($isActive) {
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
            ->orderBy('payments.transaction_date', 'desc')
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

        // Transformar datos para la tabla (fila por pago)
        $items = collect($payments->items())->map(function ($payment) {
            $order = $payment->order;
            $participant = $order?->participant;
            $program = $order?->programCourse;
            $participantProgram = $order?->participantProgram;

            // Saldo usando misma lógica que vista de participantes
            $saldo = $this->calculateParticipantSaldo($participant, $program);

            // Monto liberado (descuento tipo 'released')
            $liberatedAmount = 0;
            if ($participantProgram && $participantProgram->discounts) {
                $releasedDiscount = $participantProgram->discounts->where('discount_type', 'released')->first();
                $liberatedAmount = $releasedDiscount ? $releasedDiscount->amount : 0;
            }

            // Contacto pagador (nombre del pagador desde detalle de orden)
            $firstDetail = $order?->orderDetails?->first();
            $payerContactRaw = $firstDetail?->name ?? ($participant?->full_name ?? null);
            $payerContact = $payerContactRaw ? ucwords(strtolower($payerContactRaw)) : null;
            $payerEmail = $firstDetail?->email ?? ($participant?->email ?? null);

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
                'status' => $participant?->is_active ? 'Activo' : 'Inactivo',
                'payment_or_refund' => $payment->amount ?? 0,
                'document_number' => $payment->bsale_number ?? $payment->payment_code ?? $order?->order_number ?? 'N/A',
                'document_type' => $payment->document_type ?? 'N/A',
                'payment_form' => $payment->paymentOption?->report_code ?? 'N/A',
                'payment_date' => $payment->transaction_date ? Carbon::parse($payment->transaction_date)->format('d/m/Y') : 'N/A',
                'payer_contact' => $payerContact ?? 'N/A',
                'payer_email' => $payerEmail ?? 'N/A',
                'liberated' => $liberatedAmount,
                'saldo' => $saldo,
                'sales_executive_name' => $program?->salesExecutive?->name ?? 'N/A',
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
            'order.participant',
            'order.programCourse.salesExecutive',
            'order.orderDetails',
            'order.participantProgram',
            'order.participantProgram.discounts',
            'order.payments'
        ])->whereIn('payments.status', ['approved', 'completed']);

        // Aplicar filtros
        if (!empty($normalizedFilters['dateFrom'])) {
            $query->whereDate('payments.transaction_date', '>=', $normalizedFilters['dateFrom']);
        }
        if (!empty($normalizedFilters['dateTo'])) {
            $query->whereDate('payments.transaction_date', '<=', $normalizedFilters['dateTo']);
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
        // Filtro por estado activo/inactivo del participante
        if (!empty($normalizedFilters['status'])) {
            $isActive = $normalizedFilters['status'] === 'active';
            $query->whereHas('order.participant', function ($q) use ($isActive) {
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
            ->orderBy('payments.transaction_date', 'asc')
            ->select('payments.*')
            ->get();

        // Log de resultados para exportación
        \Illuminate\Support\Facades\Log::info('Datos para exportación', [
            'total_payments' => $payments->count(),
            'filters' => $normalizedFilters,
            'first_payment' => $payments->first() ? 'existe' : 'no hay pagos'
        ]);

        // Transformar datos para exportación (fila por pago)
        $items = $payments->map(function ($payment) {
            $order = $payment->order;
            $participant = $order?->participant;
            $program = $order?->programCourse;
            $participantProgram = $order?->participantProgram;

            // Saldo usando misma lógica que vista de participantes
            $saldo = $this->calculateParticipantSaldo($participant, $program);

            // Monto liberado (descuento tipo 'released')
            $liberatedAmount = 0;
            if ($participantProgram && $participantProgram->discounts) {
                $releasedDiscount = $participantProgram->discounts->where('discount_type', 'released')->first();
                $liberatedAmount = $releasedDiscount ? $releasedDiscount->amount : 0;
            }

            // Contacto pagador (nombre del pagador desde detalle de orden)
            $firstDetail = $order?->orderDetails?->first();
            $payerContactRaw = $firstDetail?->name ?? ($participant?->full_name ?? null);
            $payerContact = $payerContactRaw ? ucwords(strtolower($payerContactRaw)) : null;
            $payerEmail = $firstDetail?->email ?? ($participant?->email ?? null);

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
                'status' => $participant?->is_active ? 'Activo' : 'Inactivo',
                'payment_or_refund' => $payment->amount ?? 0,
                'document_number' => $payment->bsale_number ?? $payment->payment_code ?? $order?->order_number ?? 'N/A',
                'document_type' => $payment->document_type ?? 'N/A',
                'payment_form' => $payment->paymentOption?->report_code ?? 'N/A',
                'payment_date' => $payment->transaction_date ? Carbon::parse($payment->transaction_date)->format('d/m/Y') : 'N/A',
                'payer_contact' => $payerContact ?? 'N/A',
                'payer_email' => $payerEmail ?? 'N/A',
                'liberated' => $liberatedAmount,
                'saldo' => $saldo,
                'sales_executive_name' => $program?->salesExecutive?->name ?? 'N/A',
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


