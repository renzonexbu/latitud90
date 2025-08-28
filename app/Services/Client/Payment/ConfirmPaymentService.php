<?php

namespace App\Services\Client\Payment;

use App\Models\Program;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Participant;
use App\Models\InstallmentPlan;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;

class ConfirmPaymentService
{
    use SystemLogging;
    public function getConfirmationDetails($programId, $participantId = null, $rut = null)
    {
        $program = Program::with(['course.participants'])->find($programId);
        if (!$program) {
            return null;
        }

        // Buscar datos reales del participante por RUT
        $formData = $this->getFormDataFromRut($rut);
        $participant = null;
        if ($rut) {
            // Limpiar el documento de puntos y guiones
            $cleanDocument = preg_replace('/[.-]/', '', $rut);
            
            // Buscar primero por RUT (compatibilidad)
            $participant = Participant::where('document_number', $cleanDocument)
                ->whereHas('documentType', function($query) {
                    $query->where('name', 'RUT');
                })
                ->first();
                
            // Si no se encuentra por RUT, buscar por cualquier tipo de documento
            if (!$participant) {
                $participant = Participant::where('document_number', $cleanDocument)->first();
            }
        } elseif ($participantId) {
            $participant = Participant::find($participantId);
        }

        // Calcular montos por participante
        $isEnrolled = false;
        $participantAmount = null;
        $participantAdjustments = 0.0;
        $participantTotalAmount = (float) $program->trip_price;
        $paidAmount = 0.0;
        $participantBalance = (float) $program->trip_price;
        $paymentPercentage = 0.0;
        
        // Inicializar priceData con valores por defecto
        $priceData = [
            'base_price' => (float) $program->trip_price,
            'adjustments' => 0.0,
            'final_price' => (float) $program->trip_price
        ];

        if ($participant && $program->course) {
            $pivotParticipant = $program->course->participants
                ->firstWhere('id', $participant->id);
            if ($pivotParticipant) {
                $isEnrolled = true;
                
                // Usar el helper para calcular el precio final con descuentos
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
                $participantTotalAmount = $priceData['final_price'];
                
                // Log para debug
                $this->logInfo('ConfirmPaymentService: Cálculo de precios del participante', [
                    'participant_id' => $participant->id,
                    'program_id' => $program->id,
                    'program_trip_price' => $program->trip_price,
                    'priceData' => $priceData,
                    'participantTotalAmount' => $participantTotalAmount,
                    'isEnrolled' => $isEnrolled
                ]);
                
                // Sumar pagos aprobados y completados del participante para este programa
                $paidAmount = (float) \App\Models\Payment::whereHas('order', function ($q) use ($participant, $program) {
                        $q->where('participant_id', $participant->id)
                          ->where('program_id', $program->id);
                    })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');
                $paidAmount = round($paidAmount, 2);
                $participantBalance = max(round($participantTotalAmount - $paidAmount, 2), 0);
                $paymentPercentage = $participantTotalAmount > 0
                    ? round(($paidAmount / $participantTotalAmount) * 100, 2)
                    : 0.0;
                    
                // Log para debug de pagos
                $this->logInfo('ConfirmPaymentService: Información de pagos del participante', [
                    'participant_id' => $participant->id,
                    'program_id' => $program->id,
                    'paidAmount' => $paidAmount,
                    'participantBalance' => $participantBalance,
                    'paymentPercentage' => $paymentPercentage
                ]);
            }
        }
        $paymentData = $this->getPaymentDataFromSession();
        // Plan de cuotas activo usando la nueva arquitectura
        $activeInstallment = null;
        $paymentPlanLocked = false;
        if ($participant) {
            $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                ->where('program_id', $program->id)
                ->where('status', 'active')
                ->first();
                
            if ($installmentPlan) {
                // Verificar si hay cuotas pagadas
                $paidInstallments = $installmentPlan->installments()->where('status', 'paid')->count();
                $paymentPlanLocked = $paidInstallments > 0;
                
                if ($paymentPlanLocked) {
                    // Obtener la próxima cuota pendiente
                    $nextInstallment = $installmentPlan->installments()
                        ->where('status', 'pending')
                        ->orderBy('due_date')
                        ->first();
                        
                    if ($nextInstallment) {
                        $activeInstallment = [
                            'number' => (int) $nextInstallment->installment_number,
                            'total' => (int) $installmentPlan->total_installments,
                            'amount' => round(((float) $nextInstallment->amount), 2),
                            'due_date' => optional($nextInstallment->due_date)->toDateString(),
                        ];
                    }
                }
            }
        }

        // Cargar opciones de pago habilitadas (nuevo esquema payment_options + pivote)
        $fullPaymentOptionCodes = DB::table('program_payment_option as ppo')
            ->join('payment_options as po', 'po.id', '=', 'ppo.payment_option_id')
            ->where('ppo.program_id', $program->id)
            ->where('ppo.enabled', true)
            ->where('po.mode', 'full')
            ->pluck('po.code')
            ->toArray();

        $lat90PaymentOptions = DB::table('program_payment_option as ppo')
            ->join('payment_options as po', 'po.id', '=', 'ppo.payment_option_id')
            ->select(['po.code', 'po.label'])
            ->where('ppo.program_id', $program->id)
            ->where('ppo.enabled', true)
            ->where('po.mode', 'lat90')
            ->get()
            ->map(function ($row) {
                return ['code' => $row->code, 'label' => $row->label];
            })
            ->toArray();

        $result = [
            'program' => [
                'id' => $program->id,
                'name' => $program->name,
                'destination' => $program->destination,
                // Mostrar siempre valores para el participante
                // Base para UI (valor del participante)
                'trip_price' => $participantTotalAmount,
                'departure_date' => $program->departure_date,
                'final_payment_date' => $program->final_payment_date,
                'max_installments' => $program->max_installments,
                // Propiedades necesarias para PaymentPanel
                'enable_total_payment' => $program->enable_total_payment,
                'enable_lat90_payment' => $program->enable_lat90_payment,
                'full_payment_options' => $fullPaymentOptionCodes,
                'lat90_payment_options' => $lat90PaymentOptions,
                'lat90_max_installments' => $program->lat90_max_installments,
                // Montos por participante
                'participant_amount' => $priceData['base_price'],
                'participant_adjustments' => $priceData['adjustments'],
                'participant_total_due' => $participantTotalAmount,
                'paidAmount' => $paidAmount,
                'participant_balance' => $participantBalance,
                'paymentPercentage' => $paymentPercentage,
                'payment_plan_locked' => $paymentPlanLocked,
                'active_installment' => $activeInstallment,
            ],
            'participant' => $participantId ? [
                'id' => $participantId,
            ] : null,
            'form_data' => $formData,
            'payment_data' => $paymentData,
            'confirmation_number' => 'CONF-' . time() . '-' . rand(1000, 9999),
            'status' => 'confirmed'
        ];
        
        // Log para debug del resultado final
        $this->logInfo('ConfirmPaymentService: Resultado final', [
            'program_id' => $program->id,
            'participant_id' => $participantId,
            'rut' => $rut,
            'trip_price' => $result['program']['trip_price'],
            'participant_total_due' => $result['program']['participant_total_due'],
            'participant_balance' => $result['program']['participant_balance'],
            'participant_amount' => $result['program']['participant_amount'],
            'participant_adjustments' => $result['program']['participant_adjustments']
        ]);
        
        return $result;
    }

    private function getFormDataFromRut($rut)
    {
        if (!$rut) {
            return $this->getFormDataFromSession();
        }

        // Buscar participante por documento en la tabla participants
        // Limpiar el documento de puntos y guiones
        $cleanDocument = preg_replace('/[.-]/', '', $rut);
        
        // Buscar primero por RUT (compatibilidad)
        $participant = Participant::where('document_number', $cleanDocument)
            ->whereHas('documentType', function($query) {
                $query->where('name', 'RUT');
            })
            ->first();
            
        // Si no se encuentra por RUT, buscar por cualquier tipo de documento
        if (!$participant) {
            $participant = Participant::where('document_number', $cleanDocument)->first();
        }
        
        if ($participant) {
            // Usar directamente el accessor full_name del modelo que ya está correctamente implementado
            $formData = [
                'name' => $participant->full_name,
                'document_number' => $participant->document_number,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'code_phone' => $participant->code_phone ?? '+56',
                'country' => $participant->country ?? 'Chile',
                'region' => '',
                'city' => '',
                'termsAccepted' => true,
                'marketingAccepted' => true
            ];
            
            return $formData;
        }

        // Si no se encuentra, devolver datos por defecto
        return $this->getFormDataFromSession();
    }

    private function getFormDataFromSession()
    {
        // En un caso real, esto vendría de la base de datos
        return [
            'name' => 'Usuario Ejemplo',
            'document_number' => '12345678-9',
            'email' => 'usuario@ejemplo.com',
            'phone' => '912345678',
            'code_phone' => '+56',
            'country' => 'Chile',
            'region' => 'metropolitana',
            'city' => 'santiago',
            'termsAccepted' => true,
            'marketingAccepted' => true
        ];
    }

    private function getPaymentDataFromSession()
    {
        // En un caso real, esto vendría de la base de datos
        return [
            'paymentType' => 'total',
            'paymentMethod' => 'debit',
            'installments' => 1
        ];
    }
}
