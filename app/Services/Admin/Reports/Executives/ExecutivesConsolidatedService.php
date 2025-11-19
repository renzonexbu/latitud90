<?php

namespace App\Services\Admin\Reports\Executives;

use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ExecutivesConsolidatedService
{
    /**
     * Retorna datos para Consolidado de Área Ingresos (apoderados)
     */
    public function getConsolidated(array $filters): array
    {
        $page = (int)($filters['page'] ?? 1);
        $perPage = 25;

        // Consulta base: filas por pago (ingresos y devoluciones)
        $query = Payment::with([
            'paymentOption',
            'order.participant',
            'order.programCourse.salesExecutive',
            'order.orderDetails',
            'order.participantProgram',
            'order.participantProgram.discounts',
            'order.payments'
        ])->where('status', 'completed');

        // Aplicar filtros
        if (!empty($filters['dateFrom'])) {
            $query->whereDate('transaction_date', '>=', $filters['dateFrom']);
        }
        if (!empty($filters['dateTo'])) {
            $query->whereDate('transaction_date', '<=', $filters['dateTo']);
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

        // Log de la consulta SQL
        \Illuminate\Support\Facades\Log::info('Consulta SQL generada', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $filters
        ]);

        // Obtener pagos con paginación
        $payments = $query->orderBy('transaction_date', 'desc')->paginate($perPage, ['*'], 'page', $page);

        // Si no hay pagos con filtros, usar todos los pagos sin filtros de fecha
        if ($payments->count() === 0) {
            \Illuminate\Support\Facades\Log::warning('No hay pagos con filtros aplicados, usando todos los pagos sin filtros de fecha');
            $payments = Payment::with([
                'paymentOption',
                'order.participant',
                'order.programCourse.salesExecutive',
                'order.orderDetails',
                'order.participantProgram',
                'order.participantProgram.discounts',
                'order.payments'
            ])->where('status', 'completed')->orderBy('transaction_date', 'desc')->get();
            
            \Illuminate\Support\Facades\Log::info('Pagos obtenidos sin filtros de fecha', [
                'total_payments_no_filter' => $payments->count()
            ]);
        }

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

            // Totales para liberado
            $orderTotalPaid = ($order?->payments ?? collect())->where('status', 'completed')->sum('amount');
            $price = $participantProgram?->individual_price ?? ($order?->final_amount ?? $order?->total_amount ?? 0);
            $isLiberated = ($price - $orderTotalPaid) <= 0;

            // Descuentos/Becas
            $scholarship = 0;
            if ($participantProgram && method_exists($participantProgram, 'discounts')) {
                $scholarship = $participantProgram->discounts()->sum('amount');
            }

            // Monto liberado (descuento tipo 'released')
            $liberatedAmount = 0;
            if ($participantProgram && $participantProgram->discounts) {
                $releasedDiscount = $participantProgram->discounts->where('discount_type', 'released')->first();
                $liberatedAmount = $releasedDiscount ? $releasedDiscount->amount : 0;
            }

            // Contacto pagador (nombre del pagador desde detalle de orden)
            $firstDetail = $order?->orderDetails?->first();
            $payerContact = $firstDetail->full_name ?? ($participant?->full_name ?? null);
            $payerEmail = $firstDetail->email ?? ($participant?->email ?? null);

            // Determinar Pago o Devolución
            $isRefund = ($payment->document_type === 'BC') || ($payment->paymentOption?->gateway_code === 'refund');

            return [
                'id' => $payment->id,
                'program_number' => $program?->code ?? 'N/A',
                'identification_number' => $participant?->document_number ?? 'N/A',
                'full_name' => $participant?->full_name ?? 'N/A',
                'status' => $participant?->is_active ? 'Activo' : 'Inactivo',
                'payment_or_refund' => $payment->amount ?? 0,
                'document_number' => $order?->order_number ?? 'N/A',
                'document_type' => $payment->document_type ?? 'N/A',
                'payment_form' => $payment->paymentOption?->report_code ?? 'N/A',
                'payment_date' => $payment->transaction_date ? Carbon::parse($payment->transaction_date)->format('d/m/Y') : 'N/A',
                'payer_contact' => $payerContact ?? 'N/A',
                'payer_email' => $payerEmail ?? 'N/A',
                'scholarship_or_grant' => $scholarship,
                'liberated' => $liberatedAmount,
                'price' => 0,
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
                'dateFrom' => $filters['dateFrom'] ?? Carbon::now('America/Santiago')->subMonth()->format('Y-m-d'),
                'dateTo' => $filters['dateTo'] ?? Carbon::now('America/Santiago')->format('Y-m-d'),
                'programId' => $filters['programId'] ?? "",
                'salesExecutiveId' => $filters['salesExecutiveId'] ?? null,
            ],
            'summary' => $summary,
            'programs' => \App\Models\ProgramCourse::select('id', 'code', 'name')->where('active', true)->with('program:id,destination')->orderBy('code')->get(),
            'salesExecutives' => \App\Models\SalesExecutive::select('id', 'name')->orderBy('name')->get(),
        ];
    }

    /**
     * Retorna datos para exportación sin paginación
     */
    public function getConsolidatedForExport(array $filters): array
    {
        // Normalizar filtros: evitar que el string 'null' o vacío aplique filtros
        $normalizedFilters = $filters;
        foreach (['programId', 'salesExecutiveId'] as $key) {
            if (isset($normalizedFilters[$key]) && ($normalizedFilters[$key] === 'null' || $normalizedFilters[$key] === '')) {
                $normalizedFilters[$key] = null;
            }
        }

        // Consulta base: filas por pago (ingresos y devoluciones) - SIN PAGINACIÓN
        $query = Payment::with([
            'paymentOption',
            'order.participant',
            'order.programCourse.salesExecutive',
            'order.orderDetails',
            'order.participantProgram',
            'order.participantProgram.discounts',
            'order.payments'
        ])->where('status', 'completed');

        // Aplicar filtros
        if (!empty($normalizedFilters['dateFrom'])) {
            $query->whereDate('transaction_date', '>=', $normalizedFilters['dateFrom']);
        }
        if (!empty($normalizedFilters['dateTo'])) {
            $query->whereDate('transaction_date', '<=', $normalizedFilters['dateTo']);
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

        // Log de la consulta SQL para exportación
        \Illuminate\Support\Facades\Log::info('Consulta SQL para exportación', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'filters' => $normalizedFilters
        ]);

        // Obtener TODOS los pagos sin paginación
        $payments = $query->orderBy('transaction_date', 'desc')->get();

        // Si no hay pagos con filtros, replicar el fallback del listado: usar todos los pagos sin filtros de fecha
        if ($payments->count() === 0) {
            \Illuminate\Support\Facades\Log::warning('No hay pagos con filtros (export), usando todos los pagos sin filtros de fecha');
            $payments = Payment::with([
                'paymentOption',
                'order.participant',
                'order.programCourse.salesExecutive',
                'order.orderDetails',
                'order.participantProgram',
                'order.participantProgram.discounts',
                'order.payments'
            ])->where('status', 'completed')->orderBy('transaction_date', 'desc')->get();
        }

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

            // Totales para liberado
            $orderTotalPaid = ($order?->payments ?? collect())->where('status', 'completed')->sum('amount');
            $price = $participantProgram?->individual_price ?? ($order?->total_amount ?? 0);
            $isLiberated = ($price - $orderTotalPaid) <= 0;

            // Descuentos/Becas
            $scholarship = 0;
            if ($participantProgram && method_exists($participantProgram, 'discounts')) {
                $scholarship = $participantProgram->discounts()->sum('amount');
            }

            // Monto liberado (descuento tipo 'released')
            $liberatedAmount = 0;
            if ($participantProgram && $participantProgram->discounts) {
                $releasedDiscount = $participantProgram->discounts->where('discount_type', 'released')->first();
                $liberatedAmount = $releasedDiscount ? $releasedDiscount->amount : 0;
            }

            // Contacto pagador (nombre del pagador desde detalle de orden)
            $firstDetail = $order?->orderDetails?->first();
            $payerContact = $firstDetail->full_name ?? ($participant?->full_name ?? null);
            $payerEmail = $firstDetail->email ?? ($participant?->email ?? null);

            // Determinar Pago o Devolución
            $isRefund = ($payment->document_type === 'BC') || ($payment->paymentOption?->gateway_code === 'refund');

            return [
                'id' => $payment->id,
                'program_number' => $program?->code ?? 'N/A',
                'identification_number' => $participant?->document_number ?? 'N/A',
                'full_name' => $participant?->full_name ?? 'N/A',
                'status' => $participant?->is_active ? 'Activo' : 'Inactivo',
                'payment_or_refund' => $payment->amount ?? 0,
                'document_number' => $order?->order_number ?? 'N/A',
                'document_type' => $payment->document_type ?? 'N/A',
                'payment_form' => $payment->paymentOption?->report_code ?? 'N/A',
                'payment_date' => $payment->transaction_date ? Carbon::parse($payment->transaction_date)->format('d/m/Y') : 'N/A',
                'payer_contact' => $payerContact ?? 'N/A',
                'payer_email' => $payerEmail ?? 'N/A',
                'scholarship_or_grant' => $scholarship,
                'liberated' => $liberatedAmount,
                'price' => 0,
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


