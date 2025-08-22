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
    public function recordPaymentInitiated(Request $request, $programId, $participantRut, $paymentData, $orderData = []): void
    {
        $analytics = $this->getOrCreateAnalyticsRecord($request);
        if ($analytics) {
            // Determinar si es pago en cuotas o pago total
            $isInstallment = isset($paymentData['installments']) && $paymentData['installments'] > 1;
            $paymentType = $isInstallment ? 'Cuota Lat90' : 'Pago Total';
            
            $updateData = [
                'payment_initiated_at' => now(),
                'payment_method' => $paymentData['payment_method'] ?? null,
                'payment_amount' => $paymentData['amount'] ?? null,
                'payment_type' => $paymentType,
                'installments_count' => $isInstallment ? ($paymentData['installments'] ?? 1) : null,
                'order_id' => $orderData['order_id'] ?? null,
                'order_detail_id' => $orderData['order_detail_id'] ?? null,
                'order_number' => $orderData['order_number'] ?? null,
            ];
            
            $analytics->update($updateData);
            
            // Calcular tiempo desde confirmation view si existe
            if ($analytics->confirmation_view_at) {
                $timeToPayment = $analytics->confirmation_view_at->diffInSeconds(now());
                $analytics->update(['time_to_payment' => $timeToPayment]);
            }
            
            Log::info('Analytics: Payment initiated recorded', [
                'session_id' => $analytics->session_id,
                'order_id' => $orderData['order_id'] ?? 'NULL',
                'order_detail_id' => $orderData['order_detail_id'] ?? 'NULL',
                'order_number' => $orderData['order_number'] ?? 'NULL',
                'payment_type' => $paymentType,
                'payment_amount' => $paymentData['amount'] ?? 'NULL'
            ]);
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
        // Usar session_id del paymentData si está disponible, sino buscar en la orden
        $sessionId = $paymentData['session_id'] ?? $orderDetail->order->session_id ?? null;
        
        Log::info('Analytics: recordPaymentCompletedFromBackend called', [
            'order_detail_id' => $orderDetail->id,
            'order_id' => $orderDetail->order_id,
            'session_id_from_payment_data' => $paymentData['session_id'] ?? 'NULL',
            'session_id_from_order' => $orderDetail->order->session_id ?? 'NULL',
            'session_id_final' => $sessionId,
            'installment_number' => $orderDetail->installment_number ?? 'NULL',
            'amount' => $orderDetail->amount
        ]);
        
        if (!$sessionId) {
            Log::warning('Analytics: No session_id found in payment data or order');
            return;
        }
        
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        Log::info('Analytics: Record found', [
            'session_id' => $sessionId,
            'analytics_found' => $analytics ? 'YES' : 'NO',
            'analytics_id' => $analytics ? $analytics->id : 'NULL'
        ]);
        
        if (!$analytics) {
            // Si no existe el registro de analytics, crearlo con los datos básicos
            $analytics = EcommerceAnalytics::create([
                'session_id' => $sessionId,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'Unknown',
                'referrer' => request()->header('referer') ?? null,
                'participant_rut' => $orderDetail->order->participant->document_number ?? null,
                'participant_id' => $orderDetail->order->participant_id,
                'program_id' => $orderDetail->order->program_id,
                'program_name' => $orderDetail->order->program->name ?? null,
                'hero_search_at' => now()->subMinutes(5), // Simular tiempo de búsqueda
                'program_list_view_at' => now()->subMinutes(4), // Simular tiempo de vista de lista
                'program_detail_view_at' => now()->subMinutes(3), // Simular tiempo de vista de detalle
                'payment_details_view_at' => now()->subMinutes(2), // Simular tiempo de vista de detalles de pago
                'confirmation_view_at' => now()->subMinutes(1), // Simular tiempo de vista de confirmación
                'payment_initiated_at' => now()->subMinutes(1), // Simular tiempo de inicio de pago
            ]);
            
            Log::info('Analytics: Created new record', [
                'session_id' => $sessionId,
                'analytics_id' => $analytics->id
            ]);
        }
        
        // Determinar si es pago en cuotas o pago total
        $isInstallment = $orderDetail->installment_number !== null;
        $paymentType = $isInstallment ? 'Cuota Lat90' : 'Pago Total';
        
        // Para cuotas, el installments_count debe ser el número de cuota específica
        // Para pago total, debe ser null o 0
        $installmentsCount = null;
        if ($isInstallment) {
            $installmentsCount = $orderDetail->installment_number;
        }
        
        // Asignar los valores directamente al modelo
        $analytics->payment_completed_at = now();
        $analytics->payment_status = 'completed';
        $analytics->order_number = $orderDetail->order->order_number ?? null;
        $analytics->order_id = $orderDetail->order_id;
        $analytics->order_detail_id = $orderDetail->id;
        $analytics->payment_amount = $orderDetail->amount;
        $analytics->payment_method = $paymentData['payment_method'] ?? $orderDetail->paymentGateway->name ?? null;
        $analytics->payment_type = $paymentType;
        $analytics->installments_count = $installmentsCount;
        
        Log::info('Analytics: About to save with data', [
            'payment_type' => $paymentType,
            'installments_count' => $installmentsCount,
            'order_id' => $orderDetail->order_id,
            'order_detail_id' => $orderDetail->id,
            'payment_amount' => $orderDetail->amount,
            'payment_method' => $paymentData['payment_method'] ?? $orderDetail->paymentGateway->name ?? null,
            'order_number' => $orderDetail->order->order_number ?? null
        ]);
        
        $saveResult = $analytics->save();
        
        Log::info('Analytics: Save result', [
            'save_success' => $saveResult ? 'YES' : 'NO'
        ]);
        
        Log::info('Analytics: Payment data updated successfully', [
            'session_id' => $sessionId,
            'payment_type' => $analytics->payment_type,
            'installments_count' => $analytics->installments_count,
            'order_id' => $analytics->order_id,
            'order_detail_id' => $analytics->order_detail_id,
            'payment_amount' => $analytics->payment_amount,
            'payment_method' => $analytics->payment_method,
            'order_number' => $analytics->order_number
        ]);
        
        // Calcular time_to_completion si existe payment_initiated_at
        if ($analytics->payment_initiated_at) {
            $timeToCompletion = $analytics->payment_initiated_at->diffInSeconds(now());
            $analytics->update(['time_to_completion' => $timeToCompletion]);
        }
        
        // Calcular time_to_payment si existe confirmation_view_at
        if ($analytics->confirmation_view_at && $analytics->payment_initiated_at) {
            $timeToPayment = $analytics->confirmation_view_at->diffInSeconds($analytics->payment_initiated_at);
            $updateData['time_to_payment'] = $timeToPayment;
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
        
        if (!$analytics) {
            // Si no existe el registro de analytics, crearlo con los datos básicos
            $analytics = EcommerceAnalytics::create([
                'session_id' => $sessionId,
                'ip_address' => request()->ip() ?? '127.0.0.1',
                'user_agent' => request()->userAgent() ?? 'Unknown',
                'referrer' => request()->header('referer') ?? null,
                'participant_rut' => $orderDetail->order->participant->document_number ?? null,
                'participant_id' => $orderDetail->order->participant_id,
                'program_id' => $orderDetail->order->program_id,
                'program_name' => $orderDetail->order->program->name ?? null,
                'hero_search_at' => now()->subMinutes(5), // Simular tiempo de búsqueda
                'program_list_view_at' => now()->subMinutes(4), // Simular tiempo de vista de lista
                'program_detail_view_at' => now()->subMinutes(3), // Simular tiempo de vista de detalle
                'payment_details_view_at' => now()->subMinutes(2), // Simular tiempo de vista de detalles de pago
                'confirmation_view_at' => now()->subMinutes(1), // Simular tiempo de vista de confirmación
                'payment_initiated_at' => now()->subMinutes(1), // Simular tiempo de inicio de pago
            ]);
        }
        
        // Determinar si es pago en cuotas o pago total
        $isInstallment = $orderDetail->installment_number !== null;
        $paymentType = $isInstallment ? 'Cuota Lat90' : 'Pago Total';
        
        // Para cuotas, el installments_count debe ser el número de cuota específica
        // Para pago total, debe ser null o 0
        $installmentsCount = null;
        if ($isInstallment) {
            $installmentsCount = $orderDetail->installment_number;
        }
        
        // Asignar los valores directamente al modelo
        $analytics->payment_failed_at = now();
        $analytics->payment_status = 'failed';
        $analytics->payment_type = $paymentType;
        $analytics->installments_count = $installmentsCount;
        
        $analytics->save();
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
