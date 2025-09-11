<?php

namespace App\Services\Client\Programs;

use App\Models\Participant;
use App\Models\Course;
use App\Models\Program;
use App\Models\Payment;
use App\Models\OrderDetail;
use App\Models\InstallmentPlan;
use App\Models\Order;
use App\Helpers\ParticipantPriceHelper;
use App\Traits\SystemLogging;
use Illuminate\Support\Facades\DB;

class ProgramService
{
    use SystemLogging;
    public function getParticipantByDocument(string $document, string $documentType): ?Participant
    {
        // Limpiar el documento de puntos y guiones
        $cleanDocument = $this->cleanDocument($document);
        
        // Buscar el participante en la base de datos usando join con la tabla document
        $participant = Participant::join('document', 'participants.document_type', '=', 'document.id')
            ->where('participants.document_number', $cleanDocument)
            ->where('document.name', $documentType)
            ->select('participants.*')
            ->with(['courses', 'emergencyContacts'])
            ->first();
            
        return $participant;
    }

    public function getParticipantByRut(string $rut): ?Participant
    {
        // Mantener compatibilidad con el método anterior
        return $this->getParticipantByDocument($rut, 'RUT');
    }

    public function getAvailablePrograms(Participant $participant): array
    {
        // Obtener solo los programas donde el participante esté inscrito
        $programs = $participant->programs()
            ->where('active', true)
            ->with(['course.institution', 'features', 'requirements'])
            ->get();

        $availablePrograms = [];

        foreach ($programs as $program) {
            // Verificar si el participante ya está inscrito en este programa
            $isEnrolled = $participant->courses()
                ->where('course_id', $program->course_id)
                ->exists();

            // Contar participantes inscritos en este programa
            $enrolledCount = 0;
            if ($program->course) {
                $enrolledCount = $program->course->participants()->count();
            }

            // Calcular montos pagados y adeudados por participante
            $paymentPercentage = 0;
            $paidAmount = 0;
            $totalAmount = $program->trip_price; // total de referencia si no hay inscripción
            $participantTotalAmount = $program->trip_price; // total a pagar por participante (base + ajuste)
            $participantBalance = $program->trip_price; // saldo remanente por defecto

            if (!$isEnrolled) {
                $availablePrograms[] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'trip_description' => $program->trip_description,
                    'destination' => $program->destination,
                    'departure_date' => $program->departure_date,
                    'trip_price' => $program->trip_price,
                    'course' => $program->course,
                    'images' => $program->images,
                    'paymentPercentage' => $paymentPercentage,
                    'paidAmount' => $paidAmount,
                    'totalAmount' => $participantTotalAmount,
                    'participant_total_due' => $participantTotalAmount, // mantener mismo nombre que Admin/Edit.vue
                    'participant_balance' => $participantBalance,
                    'status' => 'available'
                ];
            } else {
                // Si ya está inscrito, mostrar información del estado
                $enrollment = $participant->courses()
                    ->where('course_id', $program->course_id)
                    ->first();
                // Obtener enrollment_code real desde participant_program (programa-participante)
                $pp = DB::table('participant_program')
                    ->where('participant_id', $participant->id)
                    ->where('program_id', $program->id)
                    ->first();
                $enrollmentCode = $pp->enrollment_code ?? null;
                
                // Si no hay enrollment_code, generarlo como fallback
                if (!$enrollmentCode && $program->code && $participant->document_number) {
                    $enrollmentCode = $program->code . $participant->document_number;
                }
                
                // Usar el helper para calcular el precio final con descuentos
                $priceData = ParticipantPriceHelper::calculateParticipantPrice($participant, $program);
                $totalAmount = $priceData['final_price'];

                // Sumar pagos aprobados y completados del participante para este programa
                $paidAmount = (float) Payment::whereHas('order', function($q) use ($participant, $program) {
                        $q->where('participant_id', $participant->id)
                          ->where('program_id', $program->id);
                    })
                    ->whereIn('status', ['approved', 'completed'])
                    ->sum('amount');

                $paidAmount = round($paidAmount, 2);
                $participantBalance = max(round($totalAmount - $paidAmount, 2), 0);
                $participantTotalAmount = $totalAmount;
                $paymentPercentage = $totalAmount > 0 ? round(($paidAmount / $totalAmount) * 100, 2) : 0;

                // Calcular cuotas
                $totalInstallments = 0;
                $paidInstallments = 0;
                $installmentsSummary = null;

                if ($enrollment) {
                    $totalInstallments = $enrollment->pivot->total_installments ?? 0;
                    $paidInstallments = $enrollment->pivot->paid_installments ?? 0;
                    
                    // Buscar planes de cuotas del participante para este programa
                    $installmentPlans = InstallmentPlan::where('participant_id', $participant->id)
                        ->where('program_id', $program->id)
                        ->with(['installments'])
                        ->get();

                    foreach ($installmentPlans as $plan) {
                        $totalInstallments = $plan->installments->count();
                        
                        foreach ($plan->installments as $installment) {
                            if ($installment->status === 'paid') {
                                $paidInstallments++;
                            }
                        }
                    }

                    // Si no hay planes de cuotas, buscar en orders como fallback
                    // Excluir órdenes de reembolsos y pagos presenciales del conteo de cuotas
                    if ($totalInstallments == 0) {
                        $orders = Order::where('participant_id', $participant->id)
                            ->where('program_id', $program->id)
                            ->where('notes', '!=', 'Orden creada desde reembolso') // Excluir órdenes de reembolso
                            ->with(['orderDetails.paymentOption'])
                            ->get();

                        foreach ($orders as $order) {
                            if ($order->orderDetails) {
                                // Solo contar order details que NO sean de pagos presenciales NI de reembolsos
                                $ecommerceOrderDetails = $order->orderDetails->filter(function($detail) {
                                    // Excluir pagos presenciales
                                    if ($detail->paymentOption && $detail->paymentOption->mode === 'presential') {
                                        return false;
                                    }
                                    
                                    // Excluir reembolsos
                                    if ($detail->paymentOption && $detail->paymentOption->code === 'refund_credit_note') {
                                        return false;
                                    }
                                    
                                    return true;
                                });
                                
                                $totalInstallments = $ecommerceOrderDetails->count();
                                
                                foreach ($ecommerceOrderDetails as $detail) {
                                    // Verificar que el detalle no sea de un reembolso
                                    $isRefundDetail = Payment::where('order_detail_id', $detail->id)
                                        ->whereHas('paymentOption', function($q) {
                                            $q->where('code', 'refund_credit_note');
                                        })
                                        ->exists();
                                    
                                    if ($detail->is_paid && !$isRefundDetail) {
                                        $paidInstallments++;
                                    }
                                }
                            }
                        }
                    }

                    // Crear resumen de cuotas si hay cuotas
                    if ($totalInstallments > 0) {
                        $installmentsSummary = "{$paidInstallments}/{$totalInstallments}";
                    }
                }

                $availablePrograms[] = [
                    'id' => $program->id,
                    'name' => $program->name,
                    'trip_description' => $program->trip_description,
                    'destination' => $program->destination,
                    'departure_date' => $program->departure_date,
                    'trip_price' => $program->trip_price,
                    'course' => $program->course,
                    'images' => $program->images,
                    'paymentPercentage' => $paymentPercentage,
                    'paidAmount' => $paidAmount,
                    'totalAmount' => $totalAmount,
                    'participant_total_due' => $participantTotalAmount, // mismo uso que Admin/Edit.vue
                    'participant_balance' => $participantBalance,
                    'participant_amount' => $priceData['base_price'],
                    'participant_adjustments' => $priceData['adjustments'],
                    'total_installments' => $totalInstallments,
                    'paid_installments' => $paidInstallments,
                    'installments_summary' => $installmentsSummary,
                    'status' => $this->translateStatus($enrollment->pivot->status ?? 'enrolled'),
                    'enrollment_date' => $enrollment->pivot->created_at ?? null,
                    'enrollment_code' => $enrollmentCode
                ];
            }
        }

        return $availablePrograms;
    }

    public function getProgramById(int $programId): ?array
    {
        $program = Program::with(['course.institution', 'features', 'requirements'])
            ->find($programId);

        if (!$program) {
            return null;
        }

        // Contar participantes inscritos en este programa
        $enrolledCount = 0;
        if ($program->course) {
            $enrolledCount = $program->course->participants()->count();
        }

        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->trip_description,
            'institution' => $program->course->institution->name ?? 'N/A',
            'base_price' => $program->trip_price,
            'duration_days' => $this->calculateDurationDays($program->departure_date),
            'start_date' => $program->departure_date,
            'end_date' => $program->departure_date,
            'destination' => $program->destination,
            'capacity' => 50,
            'available_spots' => 50 - $enrolledCount,
            'image_url' => $program->images[0] ?? '/images/default-program.jpg',
            'itinerary' => $program->itinerary_description ?? '',
            'included_services' => $program->features->where('pivot.type', 'included')->pluck('name')->toArray(),
            'not_included_services' => $program->features->where('pivot.type', 'not_included')->pluck('name')->toArray(),
            'requirements' => $program->requirements->pluck('name')->toArray(),
            'important_notes' => $program->pillars ?? ''
        ];
    }

    private function calculateDurationDays($departureDate): int
    {
        // Por ahora retornamos un valor por defecto
        // En el futuro se puede calcular basado en el itinerario
        return 7;
    }
    
    private function cleanRut(string $rut): string
    {
        // Remover puntos y guiones del RUT
        return preg_replace('/[.-]/', '', $rut);
    }
    
    private function cleanDocument(string $document): string
    {
        // Remover puntos y guiones del documento
        return preg_replace('/[.-]/', '', $document);
    }
    
    private function translateStatus(string $status): string
    {
        $translations = [
            'pending_payment' => 'Pendiente de Pago',
            'confirmed' => 'Confirmado',
            'cancelled' => 'Cancelado',
            'enrolled' => 'Inscrito'
        ];
        
        return $translations[$status] ?? $status;
    }
}
