<?php

namespace App\Services;

use App\Models\EcommerceAnalytics;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EcommerceAnalyticsService
{
    /**
     * Obtener o crear un registro de analytics para una sesión
     */
    public function getOrCreateAnalyticsRecord(Request $request, $participantRut = null, $programId = null): EcommerceAnalytics
    {
        $sessionId = session()->getId();
        
        Log::info('EcommerceAnalyticsService: getOrCreateAnalyticsRecord', [
            'session_id' => $sessionId,
            'participant_rut' => $participantRut,
            'program_id' => $programId,
        ]);
        
        // Buscar registro existente por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        Log::info('EcommerceAnalyticsService: Buscando por session_id', [
            'session_id' => $sessionId,
            'found' => !is_null($analytics),
            'analytics_id' => $analytics ? $analytics->id : null,
        ]);
        
        // Si no encuentra por session_id, buscar por participant_rut como fallback
        if (!$analytics && $participantRut) {
            $analytics = EcommerceAnalytics::where('participant_rut', $participantRut)
                ->whereNotNull('hero_search_at')
                ->latest()
                ->first();
                
            Log::info('EcommerceAnalyticsService: Buscando por participant_rut', [
                'participant_rut' => $participantRut,
                'found' => !is_null($analytics),
                'analytics_id' => $analytics ? $analytics->id : null,
            ]);
        }
        
        if (!$analytics) {
            // Crear nuevo registro solo si no existe
            Log::info('EcommerceAnalyticsService: Creando nuevo registro');
            
            $analytics = EcommerceAnalytics::create([
                'session_id' => $sessionId,
                'visitor_id' => null, // No necesitamos visitor_id
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->header('referer'),
                'participant_rut' => $participantRut,
                'program_id' => $programId,
                'program_name' => $programId ? \App\Models\Program::find($programId)?->name : null,
            ]);
            
            Log::info('EcommerceAnalyticsService: Nuevo registro creado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            // Si encontró un registro existente, actualizar session_id si es necesario
            if ($analytics->session_id !== $sessionId) {
                Log::info('EcommerceAnalyticsService: Actualizando session_id', [
                    'old_session_id' => $analytics->session_id,
                    'new_session_id' => $sessionId,
                ]);
                $analytics->update(['session_id' => $sessionId]);
            }
        }
        
        return $analytics;
    }

    /**
     * Registrar búsqueda en el hero
     */
    public function recordHeroSearch(Request $request, $document, $documentType): string
    {
        Log::info('EcommerceAnalyticsService: recordHeroSearch', [
            'document' => $document,
            'document_type' => $documentType,
        ]);
        
        // Limpiar documento para búsqueda
        $cleanDocument = preg_replace('/[.-]/', '', $document);
        
        // Buscar si el participante existe
        $participant = Participant::where('document_number', $cleanDocument)->first();
        $participantId = $participant ? $participant->id : null;
        $participantRut = $participant ? $participant->document_number : $cleanDocument;
        
        Log::info('EcommerceAnalyticsService: Participante encontrado', [
            'clean_document' => $cleanDocument,
            'participant_exists' => !is_null($participant),
            'participant_id' => $participantId,
            'participant_rut' => $participantRut,
        ]);
        
        // Generar un session_id único para este flujo
        $sessionId = \Illuminate\Support\Str::uuid()->toString();
        
        Log::info('EcommerceAnalyticsService: Generando session_id único', [
            'session_id' => $sessionId,
        ]);
        
        $analytics = EcommerceAnalytics::create([
            'session_id' => $sessionId,
            'visitor_id' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('referer'),
            'participant_rut' => $participantRut,
            'participant_id' => $participantId,
            'hero_search_at' => now(),
            'funnel_data' => [
                'hero_search' => [
                    'document' => $document,
                    'document_type' => $documentType,
                    'participant_exists' => !is_null($participant),
                    'participant_id' => $participantId,
                ]
            ]
        ]);
        
        Log::info('EcommerceAnalyticsService: Hero search registrado', [
            'analytics_id' => $analytics->id,
            'session_id' => $sessionId,
        ]);
        
        return $sessionId;
    }

    /**
     * Registrar vista de lista de programas
     */
    public function recordProgramListView(Request $request): void
    {
        Log::info('EcommerceAnalyticsService: recordProgramListView');
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordProgramListView');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para program list view', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con program list view', [
                'analytics_id' => $analytics->id,
            ]);
            
            $analytics->update(['program_list_view_at' => now()]);
            
            Log::info('EcommerceAnalyticsService: Program list view registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar program list view', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar selección de programa
     */
    public function recordProgramSelection(Request $request, $programId, $enrollmentCode = null): void
    {
        Log::info('EcommerceAnalyticsService: recordProgramSelection', [
            'program_id' => $programId,
            'enrollment_code' => $enrollmentCode,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordProgramSelection');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            $program = \App\Models\Program::find($programId);
            
            Log::info('EcommerceAnalyticsService: Actualizando registro con selección de programa', [
                'analytics_id' => $analytics->id,
                'program_id' => $programId,
                'program_name' => $program ? $program->name : null,
            ]);
            
            $analytics->update([
                'program_id' => $programId,
                'program_name' => $program ? $program->name : null,
                'funnel_data' => array_merge($analytics->funnel_data ?? [], [
                    'program_selection' => [
                        'program_id' => $programId,
                        'program_name' => $program ? $program->name : null,
                        'enrollment_code' => $enrollmentCode,
                    ]
                ])
            ]);
            
            Log::info('EcommerceAnalyticsService: Selección de programa registrada', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar selección de programa', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar selección de método de pago
     */
    public function recordPaymentSelection(Request $request, $programId, $paymentData): void
    {
        Log::info('EcommerceAnalyticsService: recordPaymentSelection', [
            'program_id' => $programId,
            'payment_data' => $paymentData,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordPaymentSelection');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para pago', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con selección de pago', [
                'analytics_id' => $analytics->id,
                'payment_type' => $request->input('payment_type'),
                'payment_method' => $request->input('payment_method'),
                'installments' => $request->input('installments'),
            ]);
            
            // Preparar los datos del funnel
            $paymentSelectionData = [
                'payment_type' => $request->input('payment_type'),
                'payment_method' => $request->input('payment_method'),
                'installments' => $request->input('installments'),
                'amount' => $request->input('amount'),
                'terms_accepted' => $request->input('terms_accepted', false),
            ];
            
            Log::info('EcommerceAnalyticsService: Datos de payment_selection a guardar', [
                'payment_selection_data' => $paymentSelectionData,
            ]);
            
            // Obtener funnel_data actual
            $currentFunnelData = $analytics->funnel_data ?? [];
            Log::info('EcommerceAnalyticsService: Funnel data actual', [
                'current_funnel_data' => $currentFunnelData,
            ]);
            
            // Fusionar con nuevos datos
            $newFunnelData = array_merge($currentFunnelData, [
                'payment_selection' => $paymentSelectionData
            ]);
            
            Log::info('EcommerceAnalyticsService: Funnel data nuevo', [
                'new_funnel_data' => $newFunnelData,
            ]);
            
            $analytics->update([
                'funnel_data' => $newFunnelData
            ]);
            
            Log::info('EcommerceAnalyticsService: Selección de pago registrada', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar selección de pago', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar vista de detalle de programa
     */
    public function recordProgramDetailView(Request $request, $programId): void
    {
        Log::info('EcommerceAnalyticsService: recordProgramDetailView', [
            'program_id' => $programId,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordProgramDetailView');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para program detail view', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con program detail view', [
                'analytics_id' => $analytics->id,
            ]);
            
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
            
            Log::info('EcommerceAnalyticsService: Program detail view registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar program detail view', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar vista de detalles de pago
     */
    public function recordPaymentDetailsView(Request $request, $programId, $participantRut = null): void
    {
        Log::info('EcommerceAnalyticsService: recordPaymentDetailsView', [
            'program_id' => $programId,
            'participant_rut' => $participantRut,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordPaymentDetailsView');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para payment details view', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con payment details view', [
                'analytics_id' => $analytics->id,
            ]);
            
            // Obtener datos de pago del request si están disponibles
            $paymentData = [];
            if ($request->has('payment_type')) {
                $paymentData = [
                    'payment_type' => $request->input('payment_type'),
                    'payment_method' => $request->input('payment_method'),
                    'installments' => $request->input('installments'),
                    'amount' => $request->input('amount'),
                    'terms_accepted' => $request->input('terms_accepted', false),
                ];
            }
            
            $updateData = [
                'payment_details_view_at' => now(),
                'participant_rut' => $participantRut,
            ];
            
            // Si hay datos de pago, agregarlos al funnel_data
            if (!empty($paymentData)) {
                $currentFunnelData = $analytics->funnel_data ?? [];
                $updateData['funnel_data'] = array_merge($currentFunnelData, [
                    'payment_details_view' => $paymentData
                ]);
                
                Log::info('EcommerceAnalyticsService: Datos de pago agregados al payment details view', [
                    'payment_data' => $paymentData,
                ]);
            }
            
            $analytics->update($updateData);
            
            Log::info('EcommerceAnalyticsService: Payment details view registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar payment details view', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar vista de confirmación
     */
    public function recordConfirmationView(Request $request, $programId, $participantRut = null): void
    {
        Log::info('EcommerceAnalyticsService: recordConfirmationView', [
            'program_id' => $programId,
            'participant_rut' => $participantRut,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordConfirmationView');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para confirmation view', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con confirmation view', [
                'analytics_id' => $analytics->id,
            ]);
            
            $analytics->update([
                'confirmation_view_at' => now(),
                'participant_rut' => $participantRut,
            ]);
            
            Log::info('EcommerceAnalyticsService: Confirmation view registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar confirmation view', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar inicio de pago
     */
    public function recordPaymentInitiated(Request $request, $programId, $participantRut, $paymentData): void
    {
        Log::info('EcommerceAnalyticsService: recordPaymentInitiated', [
            'program_id' => $programId,
            'participant_rut' => $participantRut,
            'payment_data' => $paymentData,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordPaymentInitiated');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para payment initiated', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con payment initiated', [
                'analytics_id' => $analytics->id,
            ]);
            
            $analytics->update([
                'payment_initiated_at' => now(),
                'payment_method' => $paymentData['payment_method'] ?? null,
                'payment_amount' => $paymentData['amount'] ?? null,
            ]);
            
            // Calcular tiempo desde confirmation view si existe
            if ($analytics->confirmation_view_at) {
                $timeToPayment = $analytics->confirmation_view_at->diffInSeconds(now());
                $analytics->update(['time_to_payment' => $timeToPayment]);
            }
            
            Log::info('EcommerceAnalyticsService: Payment initiated registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar payment initiated', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar pago completado (versión para llamadas desde frontend con Request)
     */
    public function recordPaymentCompleted(Request $request, $programId, $participantRut, $paymentData, $orderData = []): void
    {
        Log::info('EcommerceAnalyticsService: recordPaymentCompleted', [
            'program_id' => $programId,
            'participant_rut' => $participantRut,
            'payment_data' => $paymentData,
            'order_data' => $orderData,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordPaymentCompleted');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para payment completed', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con payment completed', [
                'analytics_id' => $analytics->id,
            ]);
            
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
            
            Log::info('EcommerceAnalyticsService: Payment completed registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar payment completed', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar pago fallido (versión para llamadas desde frontend con Request)
     */
    public function recordPaymentFailed(Request $request, $programId, $participantRut, $errorMessage = null): void
    {
        Log::info('EcommerceAnalyticsService: recordPaymentFailed', [
            'program_id' => $programId,
            'participant_rut' => $participantRut,
            'error_message' => $errorMessage,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordPaymentFailed');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para payment failed', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con payment failed', [
                'analytics_id' => $analytics->id,
            ]);
            
            $analytics->update([
                'payment_failed_at' => now(),
                'payment_status' => 'failed',
                'notes' => $errorMessage,
            ]);
            
            Log::info('EcommerceAnalyticsService: Payment failed registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar payment failed', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar pago fallido (versión para llamadas desde backend sin Request)
     */
    public function recordPaymentFailedFromBackend($orderDetail, $errorMessage = null): void
    {
        Log::info('EcommerceAnalyticsService: recordPaymentFailedFromBackend', [
            'order_detail_id' => $orderDetail->id,
            'error_message' => $errorMessage,
        ]);
        
        // Intentar obtener session_id desde el order
        $sessionId = $orderDetail->order->session_id ?? null;
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se encontró session_id en order para payment failed');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para payment failed (backend)', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con payment failed (backend)', [
                'analytics_id' => $analytics->id,
            ]);
            
            $analytics->update([
                'payment_failed_at' => now(),
                'payment_status' => 'failed',
                'notes' => $errorMessage,
            ]);
            
            Log::info('EcommerceAnalyticsService: Payment failed registrado (backend)', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar payment failed (backend)', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar datos del formulario del comprador
     */
    public function recordBuyerFormData(Request $request, $programId, $formData): void
    {
        Log::info('EcommerceAnalyticsService: recordBuyerFormData', [
            'program_id' => $programId,
            'form_data' => $formData,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordBuyerFormData');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para buyer form data', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con buyer form data', [
                'analytics_id' => $analytics->id,
            ]);
            
            // Preparar los datos del formulario
            $buyerFormData = [
                'full_name' => $formData['fullName'] ?? null,
                'document_type' => $formData['documentType'] ?? null,
                'document_number' => $formData['documentNumber'] ?? null,
                'email' => $formData['email'] ?? null,
                'phone' => $formData['phone'] ?? null,
                'phone_code' => $formData['code_phone'] ?? null,
                'country_id' => $formData['country'] ?? null,
                'region_id' => $formData['region'] ?? null,
                'city_id' => $formData['city'] ?? null,
                'terms_accepted' => $formData['termsAccepted'] ?? false,
                'marketing_accepted' => $formData['marketingAccepted'] ?? false,
                'is_frequent_client' => $formData['isFrequentClient'] ?? false,
                'submitted_at' => now()->toISOString(),
            ];
            
            Log::info('EcommerceAnalyticsService: Datos del formulario a guardar', [
                'buyer_form_data' => $buyerFormData,
            ]);
            
            // Obtener funnel_data actual
            $currentFunnelData = $analytics->funnel_data ?? [];
            
            Log::info('EcommerceAnalyticsService: Funnel data actual antes de actualizar', [
                'current_funnel_data' => $currentFunnelData,
            ]);
            
            // Fusionar con nuevos datos
            $newFunnelData = array_merge($currentFunnelData, [
                'buyer_form_data' => $buyerFormData
            ]);
            
            Log::info('EcommerceAnalyticsService: Funnel data nuevo después de fusionar', [
                'new_funnel_data' => $newFunnelData,
            ]);
            
            $analytics->update([
                'funnel_data' => $newFunnelData
            ]);
            
            // Verificar que se guardó correctamente
            $analytics->refresh();
            Log::info('EcommerceAnalyticsService: Funnel data después de guardar', [
                'saved_funnel_data' => $analytics->funnel_data,
            ]);
            
            Log::info('EcommerceAnalyticsService: Buyer form data registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar buyer form data', [
                'session_id' => $sessionId,
            ]);
        }
    }

    /**
     * Registrar cambios en confirmación
     */
    public function recordConfirmationChanges(Request $request, $programId, $changes): void
    {
        Log::info('EcommerceAnalyticsService: recordConfirmationChanges', [
            'program_id' => $programId,
            'changes' => $changes,
        ]);
        
        // Obtener session_id desde el request
        $sessionId = $request->input('session_id');
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se proporcionó session_id en recordConfirmationChanges');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para confirmation changes', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con confirmation changes', [
                'analytics_id' => $analytics->id,
            ]);
            
            $analytics->update([
                'funnel_data' => array_merge($analytics->funnel_data ?? [], [
                    'confirmation_changes' => $changes
                ])
            ]);
            
            Log::info('EcommerceAnalyticsService: Confirmation changes registrado', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar confirmation changes', [
                'session_id' => $sessionId,
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
     * Registrar pago completado (versión para llamadas desde backend sin Request)
     */
    public function recordPaymentCompletedFromBackend($orderDetail, $paymentData = []): void
    {
        Log::info('EcommerceAnalyticsService: recordPaymentCompletedFromBackend', [
            'order_detail_id' => $orderDetail->id,
            'payment_data' => $paymentData,
        ]);
        
        // Intentar obtener session_id desde el order
        $sessionId = $orderDetail->order->session_id ?? null;
        
        if (!$sessionId) {
            Log::warning('EcommerceAnalyticsService: No se encontró session_id en order para payment completed');
            return;
        }
        
        Log::info('EcommerceAnalyticsService: Buscando registro por session_id para payment completed (backend)', [
            'session_id' => $sessionId,
        ]);
        
        // Buscar el registro por session_id
        $analytics = EcommerceAnalytics::where('session_id', $sessionId)->first();
        
        if ($analytics) {
            Log::info('EcommerceAnalyticsService: Actualizando registro con payment completed (backend)', [
                'analytics_id' => $analytics->id,
            ]);
            
            $orderData = [
                'order_number' => $orderDetail->order->order_number ?? null,
                'order_id' => $orderDetail->order_id,
                'order_detail_id' => $orderDetail->id,
            ];
            
            $analytics->update([
                'payment_completed_at' => now(),
                'payment_status' => 'completed',
                'order_number' => $orderData['order_number'],
                'order_id' => $orderData['order_id'],
                'order_detail_id' => $orderData['order_detail_id'],
                'payment_amount' => $orderDetail->amount,
                'payment_method' => $paymentData['payment_method'] ?? $orderDetail->paymentGateway->name ?? null,
            ]);
            
            // Calcular tiempo total hasta completado
            if ($analytics->payment_initiated_at) {
                $timeToCompletion = $analytics->payment_initiated_at->diffInSeconds(now());
                $analytics->update(['time_to_completion' => $timeToCompletion]);
            }
            
            Log::info('EcommerceAnalyticsService: Payment completed registrado (backend)', [
                'analytics_id' => $analytics->id,
            ]);
        } else {
            Log::warning('EcommerceAnalyticsService: No se encontró registro para actualizar payment completed (backend)', [
                'session_id' => $sessionId,
            ]);
        }
    }

}
