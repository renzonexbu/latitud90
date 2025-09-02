<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
use App\Models\Program;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;

class GetPaymentsService
{
    use AdminLogging;
    /**
     * Obtener pagos con filtros y estadísticas
     *
     * @param Request $request
     * @return array
     */
    public function execute(Request $request): array
    {
        $query = Payment::with([
            'order', 
            'order.participant.documentType', 
            'order.program.course.institution', 
            'orderDetail.country', 
            'orderDetail.region', 
            'orderDetail.city', 
            'paymentGateway', 
            'paymentOption'
        ]);

        // Aplicar filtros
        $this->applyFilters($query, $request);

        $payments = $query->latest()->paginate(15);

        // Obtener estadísticas
        $stats = $this->getStats();

        // Obtener programas para los filtros
        $programs = Program::where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        // Log the payments list view
        $this->logView(
            'payments',
            'PaymentList',
            0, // No specific resource ID for list views
            "Lista de pagos consultada - Total: {$payments->total()} registros",
            [
                'total_payments' => $payments->total(),
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'filters_applied' => $request->only(['participant_name', 'payment_status', 'program_id', 'payment_method', 'date_from', 'date_to']),
                'stats' => $stats,
            ]
        );

        return [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => $request->only(['participant_name', 'payment_status', 'program_id', 'payment_method', 'date_from', 'date_to']),
            'programs' => $programs
        ];
    }

    /**
     * Aplicar filtros a la consulta
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @return void
     */
    private function applyFilters($query, Request $request): void
    {
        // Filtro de búsqueda por nombre del participante
        if ($request->participant_name) {
            $query->whereHas('order.participant', function ($q) use ($request) {
                $q->where('first_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_last_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('first_name', 'like', '%' . $request->participant_name . '%')
                  ->orWhere('second_name', 'like', '%' . $request->participant_name . '%');
            });
        }

        // Filtro de estado del pago
        if ($request->payment_status && $request->payment_status !== 'all') {
            $query->where('status', $request->payment_status);
        }

        // Filtro de programa
        if ($request->program_id) {
            $query->whereHas('order.program', function ($q) use ($request) {
                $q->where('id', $request->program_id);
            });
        }

        // Filtro de método de pago (gateway)
        if ($request->payment_method && $request->payment_method !== 'all') {
            $query->whereHas('paymentGateway', function ($q) use ($request) {
                $q->where('code', $request->payment_method);
            });
        }

        // Filtro de fecha desde
        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        // Filtro de fecha hasta
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filtros legacy para compatibilidad
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('id', 'like', '%' . $request->search . '%')
                  ->orWhere('buy_order', 'like', '%' . $request->search . '%')
                  ->orWhere('authorization_code', 'like', '%' . $request->search . '%')
                  ->orWhereHas('order.participant', function ($sq) use ($request) {
                      $sq->where('first_last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('second_last_name', 'like', '%' . $request->search . '%')
                        ->orWhere('first_name', 'like', '%' . $request->search . '%')
                        ->orWhere('second_name', 'like', '%' . $request->search . '%');
                  })
                  ->orWhereHas('order.program', function ($sq) use ($request) {
                      $sq->where('name', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->gateway) {
            $query->whereHas('paymentGateway', function ($q) use ($request) {
                $q->where('code', $request->gateway);
            });
        }
    }

    /**
     * Obtener estadísticas de pagos
     *
     * @return array
     */
    private function getStats(): array
    {
        return [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'completed' => Payment::where('status', 'completed')->count(),
            'pending' => Payment::where('status', 'pending')->count(),
            'failed' => Payment::where('status', 'failed')->count(),
            'authorized' => Payment::where('status', 'authorized')->count(),
        ];
    }
}
