<?php

namespace App\Services;

use App\Models\EcommerceAnalytics;
use App\Models\Participant;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EcommerceAnalyticsService
{
    /**
     * Obtener o crear un registro de analytics para una sesión
     */
    public function getOrCreateAnalyticsRecord(Request $request, $participantRut = null, $programId = null): ?EcommerceAnalytics
    {
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id');
            return null;
        }
        
        // Buscar registro existente por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if (!$analytics) {
            // Crear nuevo registro
            $analytics = EcommerceAnalytics::create([
                'session_id' => $sessionId,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'participant_rut' => $participantRut,
                'program_id' => $programId,
                'program_name' => $programId ? Program::find($programId)?->name : null,
            ]);
        }
        
        return $analytics;
    }

    /**
     * Registrar búsqueda en el hero
     */
    public function recordHeroSearch(Request $request, $document, $documentType): string
    {
        // Limpiar documento para búsqueda
        $cleanDocument = preg_replace('/[.-]/', '', $document);
        
        // Buscar si el participante existe
        $participant = Participant::where('document_number', $cleanDocument)->first();
        $participantId = $participant ? $participant->id : null;
        $participantRut = $participant ? $participant->document_number : $cleanDocument;
        
        // Generar un session_id único para este flujo
        $sessionId = \Illuminate\Support\Str::uuid()->toString();
        
        EcommerceAnalytics::create([
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'participant_rut' => $participantRut,
            'participant_id' => $participantId,
            'hero_search_at' => now(),
        ]);
        
        return $sessionId;
    }

    /**
     * Registrar vista de lista de programas
     */
    public function recordProgramListView(Request $request): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update(['program_list_view_at' => now()]);
        }
    }

    /**
     * Registrar selección de programa
     */
    public function recordProgramSelection(Request $request, $programId, $enrollmentCode = null): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $program = Program::find($programId);
            $analytics->update([
                'program_id' => $programId,
                'program_name' => $program ? $program->name : null,
            ]);
        }
    }

    /**
     * Registrar selección de método de pago
     */
    public function recordPaymentSelection(Request $request, $programId, $paymentData): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update([
                'payment_type' => $request->input('payment_type'),
                'installments_count' => $request->input('installments'),
            ]);
        }
    }

    /**
     * Registrar vista de detalle de programa
     */
    public function recordProgramDetailView(Request $request, $programId): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update([
                'program_detail_view_at' => now(),
                'program_id' => $programId,
                'program_name' => Program::find($programId)?->name,
            ]);
            
            // Calcular tiempo desde hero search si existe
            if ($analytics->hero_search_at) {
                $timeToDetail = $analytics->hero_search_at->diffInSeconds(now());
                $analytics->update(['time_to_program_detail' => $timeToDetail]);
            }
        }
    }

    /**
     * Registrar vista de detalles de pago
     */
    public function recordPaymentDetailsView(Request $request, $programId, $participantRut = null): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update([
                'payment_details_view_at' => now(),
                'participant_rut' => $participantRut,
            ]);
        }
    }

    /**
     * Registrar vista de confirmación
     */
    public function recordConfirmationView(Request $request, $programId, $participantRut = null): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update([
                'confirmation_view_at' => now(),
                'participant_rut' => $participantRut,
            ]);
        }
    }

    /**
     * Registrar inicio de pago
     */
    public function recordPaymentInitiated(Request $request, $programId, $participantRut, $paymentData): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update([
                'payment_initiated_at' => now(),
                'payment_method' => $paymentData['payment_method'] ?? null,
                'payment_amount' => $paymentData['amount'] ?? null,
                'payment_type' => $paymentData['payment_type'] ?? null,
                'installments_count' => $paymentData['installments'] ?? null,
            ]);
            
            // Calcular tiempo desde confirmation view si existe
            if ($analytics->confirmation_view_at) {
                $timeToPayment = $analytics->confirmation_view_at->diffInSeconds(now());
                $analytics->update(['time_to_payment' => $timeToPayment]);
            }
        }
    }

    /**
     * Registrar pago completado
     */
    public function recordPaymentCompleted(Request $request, $programId, $participantRut, $paymentData, $orderData = []): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update([
                'payment_completed_at' => now(),
                'payment_status' => 'completed',
                'order_number' => $orderData['order_number'] ?? null,
                'order_id' => $orderData['order_id'] ?? null,
                'order_detail_id' => $orderData['order_detail_id'] ?? null,
                'payment_amount' => $paymentData['amount'] ?? null,
                'payment_method' => $paymentData['payment_method'] ?? null,
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
    public function recordPaymentFailed(Request $request, $programId, $participantRut, $errorMessage = null): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            $analytics->update([
                'payment_failed_at' => now(),
                'payment_status' => 'failed',
            ]);
        }
    }

    /**
     * Registrar pago completado desde backend
     */
    public function recordPaymentCompletedFromBackend($orderDetail, $paymentData = []): void
    {
        $sessionId = $orderDetail->order->session_id ?? null;
        if (!$sessionId) return;
        
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
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
            
            if ($analytics->payment_initiated_at) {
                $timeToCompletion = $analytics->payment_initiated_at->diffInSeconds(now());
                $analytics->update(['time_to_completion' => $timeToCompletion]);
            }
        }
    }

    /**
     * Registrar pago fallido desde backend
     */
    public function recordPaymentFailedFromBackend($orderDetail, $errorMessage = null): void
    {
        $sessionId = $orderDetail->order->session_id ?? null;
        if (!$sessionId) return;
        
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        if ($analytics) {
            $analytics->update([
                'payment_failed_at' => now(),
                'payment_status' => 'failed',
            ]);
        }
    }

    /**
     * Obtener análisis completo del funnel de conversión
     */
    public function getFunnelAnalysis($dateFrom = null, $dateTo = null): array
    {
        $query = EcommerceAnalytics::query();
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        
        $totalSessions = $query->count();
        
        if ($totalSessions === 0) {
            return [
                'total_sessions' => 0,
                'funnel_stages' => [],
                'conversion_rates' => [],
                'payment_analysis' => [],
                'top_participants' => [],
                'top_programs' => [],
            ];
        }
        
        // Análisis por etapas del funnel
        $funnelStages = [
            'hero_search' => $query->whereNotNull('hero_search_at')->count(),
            'program_list_view' => $query->whereNotNull('program_list_view_at')->count(),
            'program_detail_view' => $query->whereNotNull('program_detail_view_at')->count(),
            'payment_details_view' => $query->whereNotNull('payment_details_view_at')->count(),
            'confirmation_view' => $query->whereNotNull('confirmation_view_at')->count(),
            'payment_initiated' => $query->whereNotNull('payment_initiated_at')->count(),
            'payment_completed' => $query->whereNotNull('payment_completed_at')->count(),
            'payment_failed' => $query->whereNotNull('payment_failed_at')->count(),
        ];
        
        // Calcular tasas de conversión
        $conversionRates = [
            'hero_to_detail' => $this->calculatePercentage($funnelStages['hero_search'], $funnelStages['program_detail_view']),
            'detail_to_payment' => $this->calculatePercentage($funnelStages['program_detail_view'], $funnelStages['payment_details_view']),
            'payment_to_confirmation' => $this->calculatePercentage($funnelStages['payment_details_view'], $funnelStages['confirmation_view']),
            'confirmation_to_initiated' => $this->calculatePercentage($funnelStages['confirmation_view'], $funnelStages['payment_initiated']),
            'initiated_to_completed' => $this->calculatePercentage($funnelStages['payment_initiated'], $funnelStages['payment_completed']),
            'overall_conversion' => $this->calculatePercentage($funnelStages['hero_search'], $funnelStages['payment_completed']),
        ];
        
        // Análisis de pagos
        $paymentAnalysis = $this->getPaymentAnalysis($dateFrom, $dateTo);
        
        // Top participantes más buscados
        $topParticipants = $this->getTopParticipants($dateFrom, $dateTo);
        
        // Top programas más seleccionados
        $topPrograms = $this->getTopPrograms($dateFrom, $dateTo);
        
        return [
            'total_sessions' => $totalSessions,
            'funnel_stages' => $funnelStages,
            'conversion_rates' => $conversionRates,
            'payment_analysis' => $paymentAnalysis,
            'top_participants' => $topParticipants,
            'top_programs' => $topPrograms,
        ];
    }
    
    /**
     * Obtener análisis detallado de pagos
     */
    private function getPaymentAnalysis($dateFrom = null, $dateTo = null): array
    {
        $query = EcommerceAnalytics::query()->whereNotNull('payment_initiated_at');
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        
        $totalPayments = $query->count();
        
        if ($totalPayments === 0) {
            return [
                'total_payments' => 0,
                'payment_types' => [],
                'payment_methods' => [],
                'installments_analysis' => [],
                'status_breakdown' => [],
            ];
        }
        
        // Análisis por tipo de pago
        $paymentTypes = $query->select('payment_type', DB::raw('count(*) as count'))
            ->groupBy('payment_type')
            ->get()
            ->pluck('count', 'payment_type')
            ->toArray();
        
        // Análisis por método de pago
        $paymentMethods = $query->select('payment_method', DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->pluck('count', 'payment_method')
            ->toArray();
        
        // Análisis de cuotas
        $installmentsAnalysis = $query->select('installments_count', DB::raw('count(*) as count'))
            ->whereNotNull('installments_count')
            ->groupBy('installments_count')
            ->orderBy('installments_count')
            ->get()
            ->pluck('count', 'installments_count')
            ->toArray();
        
        // Desglose por estado
        $statusBreakdown = $query->select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->get()
            ->pluck('count', 'payment_status')
            ->toArray();
        
        return [
            'total_payments' => $totalPayments,
            'payment_types' => $paymentTypes,
            'payment_methods' => $paymentMethods,
            'installments_analysis' => $installmentsAnalysis,
            'status_breakdown' => $statusBreakdown,
        ];
    }
    
    /**
     * Obtener top participantes más buscados
     */
    private function getTopParticipants($dateFrom = null, $dateTo = null): array
    {
        $query = EcommerceAnalytics::query()->whereNotNull('hero_search_at');
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        
        return $query->select('participant_rut', DB::raw('count(*) as search_count'))
            ->whereNotNull('participant_rut')
            ->groupBy('participant_rut')
            ->orderBy('search_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    /**
     * Obtener top programas más seleccionados
     */
    private function getTopPrograms($dateFrom = null, $dateTo = null): array
    {
        $query = EcommerceAnalytics::query()->whereNotNull('program_detail_view_at');
        
        if ($dateFrom && $dateTo) {
            $query->whereBetween('created_at', [$dateFrom, $dateTo]);
        }
        
        return $query->select('program_id', 'program_name', DB::raw('count(*) as selection_count'))
            ->whereNotNull('program_id')
            ->groupBy('program_id', 'program_name')
            ->orderBy('selection_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    /**
     * Calcular porcentaje de conversión
     */
    private function calculatePercentage($total, $converted): float
    {
        if ($total === 0) return 0;
        return round(($converted / $total) * 100, 2);
    }
}
