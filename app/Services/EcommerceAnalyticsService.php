<?php

namespace App\Services;

use App\Models\EcommerceAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EcommerceAnalyticsService
{
    /**
     * Obtener o crear un registro de analytics para una sesión
     */
    public function getOrCreateAnalyticsRecord(Request $request, $participantRut = null, $programId = null): EcommerceAnalytics
    {
        $sessionId = session()->getId();
        $visitorId = $this->getVisitorId($request);
        
        // Buscar registro existente por session_id o visitor_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)
            ->orWhere('visitor_id', $visitorId)
            ->first();
        
        if (!$analytics) {
            $analytics = EcommerceAnalytics::create([
                'session_id' => $sessionId,
                'visitor_id' => $visitorId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'participant_rut' => $participantRut,
                'program_id' => $programId,
                'program_name' => $programId ? \App\Models\Program::find($programId)?->name : null,
            ]);
        }
        
        return $analytics;
    }

    /**
     * Registrar búsqueda en el hero
     */
    public function recordHeroSearch(Request $request, $participantRut = null): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request, $participantRut);
        $analytics->update([
            'hero_search_at' => now(),
            'participant_rut' => $participantRut,
        ]);
    }

    /**
     * Registrar vista de lista de programas
     */
    public function recordProgramListView(Request $request): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        $analytics->update(['program_list_view_at' => now()]);
    }

    /**
     * Registrar vista de detalle de programa
     */
    public function recordProgramDetailView(Request $request, $programId): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request, null, $programId);
        $analytics->update([
            'program_detail_view_at' => now(),
            'program_id' => $programId,
            'program_name' => \App\Models\Program::find($programId)?->name,
        ]);
        
        // Calcular tiempo desde hero search si existe
        if ($analytics->hero_search_at) {
            $timeToDetail = $analytics->hero_search_at->diffInSeconds(now());
            $analytics->update(['time_to_program_detail' => $timeToDetail]);
        }
    }

    /**
     * Registrar vista de detalles de pago
     */
    public function recordPaymentDetailsView(Request $request, $programId, $participantRut = null): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request, $participantRut, $programId);
        $analytics->update([
            'payment_details_view_at' => now(),
            'participant_rut' => $participantRut,
        ]);
    }

    /**
     * Registrar vista de confirmación
     */
    public function recordConfirmationView(Request $request, $programId, $participantRut = null): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request, $participantRut, $programId);
        $analytics->update([
            'confirmation_view_at' => now(),
            'participant_rut' => $participantRut,
        ]);
    }

    /**
     * Registrar inicio de pago
     */
    public function recordPaymentInitiated(Request $request, $programId, $participantRut, $paymentData): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request, $participantRut, $programId);
        $analytics->update([
            'payment_initiated_at' => now(),
            'payment_method' => $paymentData['paymentMethod'] ?? null,
            'payment_amount' => $paymentData['amount'] ?? null,
        ]);
        
        // Calcular tiempo desde detalle de programa si existe
        if ($analytics->program_detail_view_at) {
            $timeToPayment = $analytics->program_detail_view_at->diffInSeconds(now());
            $analytics->update(['time_to_payment' => $timeToPayment]);
        }
    }

    /**
     * Registrar pago completado
     */
    public function recordPaymentCompleted($orderDetail, $paymentData = []): void
    {
        $analytics = EcommerceAnalytics::where('participant_rut', $orderDetail->order->participant->document_number ?? null)
            ->orWhere('program_id', $orderDetail->order->program_id)
            ->latest()
            ->first();
        
        if ($analytics) {
            $analytics->update([
                'payment_completed_at' => now(),
                'payment_status' => 'completed',
                'order_number' => $orderDetail->order->order_number ?? null,
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
                'payment_amount' => $orderDetail->amount,
                'payment_method' => $paymentData['payment_method'] ?? $orderDetail->paymentGateway->name ?? null,
            ]);
            
            // Calcular tiempo total hasta completado
            if ($analytics->payment_initiated_at) {
                $timeToCompletion = $analytics->payment_initiated_at->diffInSeconds(now());
                $analytics->update(['time_to_completion' => $timeToCompletion]);
            }
        }
    }

    /**
     * Registrar pago fallido
     */
    public function recordPaymentFailed($orderDetail, $error = null): void
    {
        $analytics = EcommerceAnalytics::where('participant_rut', $orderDetail->order->participant->document_number ?? null)
            ->orWhere('program_id', $orderDetail->order->program_id)
            ->latest()
            ->first();
        
        if ($analytics) {
            $analytics->update([
                'payment_failed_at' => now(),
                'payment_status' => 'failed',
                'order_number' => $orderDetail->order->order_number ?? null,
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
                'notes' => $error,
            ]);
        }
    }

    /**
     * Obtener estadísticas del funnel
     */
    public function getFunnelStats($dateFrom = null, $dateTo = null): array
    {
        $query = EcommerceAnalytics::query();
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        
        $totalRecords = $query->count();
        
        $stats = [
            'hero_searches' => $query->whereNotNull('hero_search_at')->count(),
            'program_list_views' => $query->whereNotNull('program_list_view_at')->count(),
            'program_detail_views' => $query->whereNotNull('program_detail_view_at')->count(),
            'payment_details_views' => $query->whereNotNull('payment_details_view_at')->count(),
            'confirmation_views' => $query->whereNotNull('confirmation_view_at')->count(),
            'payments_initiated' => $query->whereNotNull('payment_initiated_at')->count(),
            'payments_completed' => $query->whereNotNull('payment_completed_at')->count(),
            'payments_failed' => $query->whereNotNull('payment_failed_at')->count(),
        ];
        
        // Calcular tasas de conversión
        $conversionRates = [
            'hero_to_detail' => $stats['hero_searches'] > 0 ? ($stats['program_detail_views'] / $stats['hero_searches']) * 100 : 0,
            'detail_to_payment' => $stats['program_detail_views'] > 0 ? ($stats['payment_details_views'] / $stats['program_detail_views']) * 100 : 0,
            'payment_to_confirmation' => $stats['payment_details_views'] > 0 ? ($stats['confirmation_views'] / $stats['payment_details_views']) * 100 : 0,
            'confirmation_to_initiated' => $stats['confirmation_views'] > 0 ? ($stats['payments_initiated'] / $stats['confirmation_views']) * 100 : 0,
            'initiated_to_completed' => $stats['payments_initiated'] > 0 ? ($stats['payments_completed'] / $stats['payments_initiated']) * 100 : 0,
            'overall_conversion' => $stats['hero_searches'] > 0 ? ($stats['payments_completed'] / $stats['hero_searches']) * 100 : 0,
        ];
        
        return [
            'stats' => $stats,
            'conversion_rates' => $conversionRates,
            'total_records' => $totalRecords,
        ];
    }

    /**
     * Obtener ID único del visitante
     */
    private function getVisitorId(Request $request): string
    {
        $visitorId = $request->cookie('visitor_id');
        
        if (!$visitorId) {
            $visitorId = Str::uuid()->toString();
            // El cookie se establecerá en el middleware
        }
        
        return $visitorId;
    }
}
