<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Payments/Index', [
            // Aquí cargarías los datos de pagos reales
            'payments' => [],
            'filters' => request()->only(['search', 'status', 'method']),
        ]);
    }

    public function show($id)
    {
        return Inertia::render('Admin/Payments/Show', [
            'payment' => [],
        ]);
    }

    public function update(Request $request, $id)
    {
        // Lógica para actualizar estado de pago
        return redirect()->route('admin.payments.index')
            ->with('message', 'Estado de pago actualizado exitosamente');
    }

    public function bulkAction(Request $request)
    {
        // Lógica para acciones en lote
        return redirect()->route('admin.payments.index')
            ->with('message', 'Acción ejecutada exitosamente');
    }

    public function export(Request $request)
    {
        // Lógica para exportar pagos
        return response()->download('payments_export.xlsx');
    }

    public function refund(Request $request, $id)
    {
        // Lógica para reembolsos
        return redirect()->route('admin.payments.index')
            ->with('message', 'Reembolso procesado exitosamente');
    }
}