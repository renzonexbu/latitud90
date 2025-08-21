<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaymentsController extends Controller
{
    public function index()
    {
        // Obtener datos de analytics
        $analyticsData = $this->getAnalyticsData();
        
        return Inertia::render('Admin/Payments/Index', [
            // Aquí cargarías los datos de pagos reales
            'payments' => [],
            'filters' => request()->only(['search', 'status', 'method']),
            'analyticsData' => $analyticsData,
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

    /**
     * Obtener datos de analytics para las gráficas
     */
    private function getAnalyticsData()
    {
        try {
            // Contar eventos del funnel de conversión usando las columnas específicas
            $heroSearchCount = \App\Models\EcommerceAnalytics::whereNotNull('hero_search_at')->count();
            $programDetailViewCount = \App\Models\EcommerceAnalytics::whereNotNull('program_detail_view_at')->count();
            $paymentDetailsViewCount = \App\Models\EcommerceAnalytics::whereNotNull('payment_details_view_at')->count();
            $paymentInitiatedCount = \App\Models\EcommerceAnalytics::whereNotNull('payment_initiated_at')->count();
            $paymentCompletedCount = \App\Models\EcommerceAnalytics::whereNotNull('payment_completed_at')->count();
            $paymentFailedCount = \App\Models\EcommerceAnalytics::whereNotNull('payment_failed_at')->count();

            // Contar selecciones de programa y datos de comprador desde funnel_data
            $programSelectionCount = \App\Models\EcommerceAnalytics::whereNotNull('funnel_data->program_selection')->count();
            $buyerFormDataCount = \App\Models\EcommerceAnalytics::whereNotNull('funnel_data->buyer_form_data')->count();

            // Analizar métodos de pago desde la columna payment_method y funnel_data
            $paymentMethodsFromColumn = \App\Models\EcommerceAnalytics::whereNotNull('payment_method')
                ->selectRaw('payment_method, COUNT(*) as count')
                ->groupBy('payment_method')
                ->pluck('count', 'payment_method')
                ->toArray();

            $paymentMethodsFromFunnel = \App\Models\EcommerceAnalytics::whereNotNull('funnel_data->payment_selection')
                ->get(['funnel_data'])
                ->map(function ($item) {
                    return $item->funnel_data['payment_selection']['payment_method'] ?? 'unknown';
                })
                ->countBy()
                ->toArray();

            // Combinar ambos métodos de pago
            $allPaymentMethods = array_merge($paymentMethodsFromColumn, $paymentMethodsFromFunnel);
            
            // Normalizar nombres de métodos de pago
            $paymentMethods = [];
            foreach ($allPaymentMethods as $method => $count) {
                $normalizedMethod = $this->normalizePaymentMethodName($method);
                if (isset($paymentMethods[$normalizedMethod])) {
                    $paymentMethods[$normalizedMethod] += $count;
                } else {
                    $paymentMethods[$normalizedMethod] = $count;
                }
            }

            // Analizar términos aceptados desde funnel_data
            $termsAcceptedCount = \App\Models\EcommerceAnalytics::whereNotNull('funnel_data->payment_selection')
                ->where('funnel_data->payment_selection->terms_accepted', true)
                ->count();
            
            $termsNotAcceptedCount = \App\Models\EcommerceAnalytics::whereNotNull('funnel_data->payment_selection')
                ->where('funnel_data->payment_selection->terms_accepted', false)
                ->count();

            // Analizar tipo de cliente desde funnel_data
            $frequentClientsCount = \App\Models\EcommerceAnalytics::whereNotNull('funnel_data->buyer_form_data')
                ->where('funnel_data->buyer_form_data->is_frequent_client', true)
                ->count();
            
            $newClientsCount = \App\Models\EcommerceAnalytics::whereNotNull('funnel_data->buyer_form_data')
                ->where('funnel_data->buyer_form_data->is_frequent_client', false)
                ->count();

            // Obtener programas más vistos
            $programViews = \App\Models\EcommerceAnalytics::whereNotNull('program_name')
                ->selectRaw('program_name, COUNT(*) as views')
                ->groupBy('program_name')
                ->orderByDesc('views')
                ->limit(5)
                ->pluck('views', 'program_name')
                ->toArray();

            return [
                'hero_search_count' => $heroSearchCount,
                'program_selection_count' => $programSelectionCount,
                'program_detail_view_count' => $programDetailViewCount,
                'payment_selection_count' => $paymentDetailsViewCount,
                'buyer_form_data_count' => $buyerFormDataCount,
                'payment_initiated_count' => $paymentInitiatedCount,
                'payment_completed_count' => $paymentCompletedCount,
                'payment_failed_count' => $paymentFailedCount,
                'payment_methods' => $paymentMethods,
                'terms_accepted_count' => $termsAcceptedCount,
                'terms_not_accepted_count' => $termsNotAcceptedCount,
                'frequent_clients_count' => $frequentClientsCount,
                'new_clients_count' => $newClientsCount,
                'program_views' => $programViews,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting analytics data: ' . $e->getMessage());
            return [
                'hero_search_count' => 0,
                'program_selection_count' => 0,
                'program_detail_view_count' => 0,
                'payment_selection_count' => 0,
                'buyer_form_data_count' => 0,
                'payment_initiated_count' => 0,
                'payment_completed_count' => 0,
                'payment_failed_count' => 0,
                'payment_methods' => [],
                'terms_accepted_count' => 0,
                'terms_not_accepted_count' => 0,
                'frequent_clients_count' => 0,
                'new_clients_count' => 0,
                'program_views' => [],
            ];
        }
    }

    /**
     * Normalizar nombres de métodos de pago para mostrar en las gráficas
     */
    private function normalizePaymentMethodName($method)
    {
        $methodMap = [
            'debit' => 'Débito',
            'credit' => 'Crédito',
            'credit_0' => 'Crédito 0 cuotas',
            'credit_3' => 'Crédito 3 cuotas',
            'credit_6' => 'Crédito 6 cuotas',
            'credit_9' => 'Crédito 9 cuotas',
            'credit_12' => 'Crédito 12 cuotas',
            'khipu' => 'Transferencia Khipu',
            'debit_credit_0' => 'Débito/Crédito 0 cuotas',
            'transbank' => 'Transbank',
            'unknown' => 'Desconocido'
        ];

        return $methodMap[$method] ?? ucfirst(str_replace('_', ' ', $method));
    }
}