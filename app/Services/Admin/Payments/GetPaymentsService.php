<?php

namespace App\Services\Admin\Payments;

use App\Models\Payment;
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
                'filters_applied' => $request->only(['search', 'status', 'gateway', 'date_from', 'date_to']),
                'stats' => $stats,
            ]
        );

        return [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'gateway', 'date_from', 'date_to'])
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
        // Filtro de búsqueda
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

        // Filtro de estado
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filtro de gateway
        if ($request->gateway) {
            $query->whereHas('paymentGateway', function ($q) use ($request) {
                $q->where('code', $request->gateway);
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
