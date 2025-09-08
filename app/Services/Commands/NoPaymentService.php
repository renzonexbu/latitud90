<?php

namespace App\Services\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Participant;
use App\Models\Order;
use App\Models\InstallmentPlan;

class NoPaymentService
{
    /**
     * Detecta participantes que no han iniciado pagos por ecommerce
     * 
     * La lógica es:
     * - Detectar todos los participantes inscritos
     * - Excluir aquellos que tengan todo pagado (monto pagado = monto a pagar)
     * - Excluir aquellos que sean liberados (descuento = 100%)
     * - Mostrar aquellos que no han generado ningún tipo de cuotas por ecommerce
     */
    public function detectParticipantsWithoutEcommercePayments()
    {
        echo "=== ANÁLISIS DE PARTICIPANTES SIN PAGOS ECOMMERCE ===\n\n";

        // 1. Participantes que NO tienen planes de cuotas (no han iniciado ecommerce)
        $participantsWithoutInstallments = $this->getParticipantsWithoutInstallmentPlans();
        
        // 2. Participantes que terminaron de pagar completamente
        $fullyPaidParticipants = $this->getFullyPaidParticipants();
        
        // 3. Participantes con descuento de liberado (100% descuento)
        $exemptParticipants = $this->getExemptParticipants();

        // Mostrar resultados
        $this->displayResults($participantsWithoutInstallments, $fullyPaidParticipants, $exemptParticipants);

        // Enviar emails a contactos de emergencia de participantes sin ecommerce
        if (!$participantsWithoutInstallments->isEmpty()) {
            echo "\n=== ENVIANDO EMAILS A CONTACTOS DE EMERGENCIA ===\n";
            $this->sendNoPaymentEmails($participantsWithoutInstallments);
        }
    }

    /**
     * Obtiene participantes que no tienen planes de cuotas creados
     */
    private function getParticipantsWithoutInstallmentPlans()
    {
        return DB::table('participants as p')
            ->leftJoin('participant_course as pc', 'p.id', '=', 'pc.participant_id')
            ->leftJoin('orders as o', function($join) {
                $join->on('p.id', '=', 'o.participant_id')
                     ->whereIn('o.status', ['pending', 'processing', 'paid']);
            })
            ->leftJoin('installment_plans as ip', 'o.id', '=', 'ip.order_id')
            ->select(
                'p.id',
                'p.first_name',
                'p.first_last_name',
                'p.second_last_name',
                'p.document_number',
                'p.email',
                'p.status as participant_status',
                'pc.status as course_status',
                'pc.individual_price',
                'pc.price_adjustments',
                DB::raw('COUNT(ip.id) as installment_plans_count'),
                DB::raw('COUNT(o.id) as orders_count')
            )
            ->where('p.is_active', true)
            ->whereIn('p.status', ['pending_payment', 'confirmed'])
            ->groupBy('p.id', 'p.first_name', 'p.first_last_name', 'p.second_last_name', 
                     'p.document_number', 'p.email', 'p.status', 'pc.status', 
                     'pc.individual_price', 'pc.price_adjustments')
            ->having('installment_plans_count', '=', 0)
            ->get();
    }

    /**
     * Obtiene participantes que han pagado completamente
     */
    private function getFullyPaidParticipants()
    {
        return DB::table('participants as p')
            ->join('participant_course as pc', 'p.id', '=', 'pc.participant_id')
            ->join('orders as o', 'p.id', '=', 'o.participant_id')
            ->join('orders_detail as od', 'o.id', '=', 'od.order_id')
            ->select(
                'p.id',
                'p.first_name',
                'p.first_last_name',
                'p.document_number',
                'p.email',
                DB::raw('SUM(CASE WHEN od.is_paid = 1 THEN od.amount ELSE 0 END) as total_paid'),
                DB::raw('o.final_amount as total_to_pay'),
                DB::raw('(SUM(CASE WHEN od.is_paid = 1 THEN od.amount ELSE 0 END) >= o.final_amount) as is_fully_paid')
            )
            ->where('p.is_active', true)
            ->groupBy('p.id', 'p.first_name', 'p.first_last_name', 'p.document_number', 
                     'p.email', 'o.final_amount')
            ->havingRaw('SUM(CASE WHEN od.is_paid = 1 THEN od.amount ELSE 0 END) >= o.final_amount')
            ->get();
    }

    /**
     * Obtiene participantes con descuento de liberado (100% descuento)
     * Incluye tanto descuentos en orders como en participant_program_discounts con tipo 'released'
     */
    private function getExemptParticipants()
    {
        // Participantes liberados por descuentos en orders
        $exemptFromOrders = DB::table('participants as p')
            ->join('participant_course as pc', 'p.id', '=', 'pc.participant_id')
            ->join('orders as o', 'p.id', '=', 'o.participant_id')
            ->select(
                'p.id',
                'p.first_name',
                'p.first_last_name',
                'p.document_number',
                'p.email',
                'pc.individual_price',
                'o.discount',
                'o.final_amount',
                DB::raw('ROUND((o.discount / o.total_amount) * 100, 2) as discount_percentage'),
                DB::raw("'order_discount' as discount_source"),
                DB::raw('NULL as comment'),
                DB::raw("'order' as discount_type")
            )
            ->where('p.is_active', true)
            ->whereRaw('o.discount >= o.total_amount OR o.final_amount <= 0');

        // Participantes liberados por participant_program_discounts con tipo 'released'
        $exemptFromProgramDiscounts = DB::table('participants as p')
            ->join('participant_program as pp', 'p.id', '=', 'pp.participant_id')
            ->join('participant_program_discounts as ppd', 'pp.id', '=', 'ppd.participant_program_id')
            ->select(
                'p.id',
                'p.first_name',
                'p.first_last_name',
                'p.document_number',
                'p.email',
                'pp.individual_price',
                DB::raw('COALESCE(ppd.amount, 0) as discount'),
                DB::raw('CASE 
                    WHEN ppd.percent IS NOT NULL THEN pp.individual_price - (pp.individual_price * ppd.percent / 100)
                    WHEN ppd.amount IS NOT NULL THEN pp.individual_price - ppd.amount
                    ELSE pp.individual_price
                END as final_amount'),
                DB::raw('COALESCE(ppd.percent, ROUND((ppd.amount / pp.individual_price) * 100, 2)) as discount_percentage'),
                DB::raw("'program_discount_released' as discount_source"),
                'ppd.comment',
                'ppd.discount_type'
            )
            ->where('p.is_active', true)
            ->where('ppd.discount_type', 'released');

        // Combinar ambas consultas
        return $exemptFromOrders->union($exemptFromProgramDiscounts)->get();
    }

    /**
     * Muestra los resultados en consola
     */
    private function displayResults($withoutInstallments, $fullyPaid, $exempt)
    {
        echo "1. PARTICIPANTES SIN PLANES DE CUOTAS (NO HAN INICIADO ECOMMERCE):\n";
        echo "================================================================\n";
        
        if ($withoutInstallments->isEmpty()) {
            echo "No se encontraron participantes sin planes de cuotas.\n\n";
        } else {
            echo "Total encontrados: " . $withoutInstallments->count() . "\n\n";
            
            foreach ($withoutInstallments as $participant) {
                $fullName = trim($participant->first_name . ' ' . $participant->first_last_name . ' ' . $participant->second_last_name);
                $price = $participant->individual_price ? number_format($participant->individual_price, 0, ',', '.') : 'N/A';
                $adjustments = $participant->price_adjustments ? number_format($participant->price_adjustments, 0, ',', '.') : '0';
                
                echo "- ID: {$participant->id}\n";
                echo "  Nombre: {$fullName}\n";
                echo "  RUT: {$participant->document_number}\n";
                echo "  Email: {$participant->email}\n";
                echo "  Estado Participante: {$participant->participant_status}\n";
                echo "  Estado Curso: {$participant->course_status}\n";
                echo "  Precio Individual: \${$price}\n";
                echo "  Ajustes de Precio: \${$adjustments}\n";
                echo "  Órdenes: {$participant->orders_count}\n";
                echo "  Planes de Cuotas: {$participant->installment_plans_count}\n";
                echo "  ---\n";
            }
        }

        echo "\n2. PARTICIPANTES QUE TERMINARON DE PAGAR:\n";
        echo "==========================================\n";
        
        if ($fullyPaid->isEmpty()) {
            echo "No se encontraron participantes que hayan terminado de pagar.\n\n";
        } else {
            echo "Total encontrados: " . $fullyPaid->count() . "\n\n";
            
            foreach ($fullyPaid as $participant) {
                $fullName = trim($participant->first_name . ' ' . $participant->first_last_name);
                $totalPaid = number_format($participant->total_paid, 0, ',', '.');
                $totalToPay = number_format($participant->total_to_pay, 0, ',', '.');
                
                echo "- ID: {$participant->id}\n";
                echo "  Nombre: {$fullName}\n";
                echo "  RUT: {$participant->document_number}\n";
                echo "  Email: {$participant->email}\n";
                echo "  Total Pagado: \${$totalPaid}\n";
                echo "  Total a Pagar: \${$totalToPay}\n";
                echo "  ---\n";
            }
        }

        echo "\n3. PARTICIPANTES CON DESCUENTO DE LIBERADO:\n";
        echo "============================================\n";
        
        if ($exempt->isEmpty()) {
            echo "No se encontraron participantes con descuento de liberado.\n\n";
        } else {
            echo "Total encontrados: " . $exempt->count() . "\n\n";
            
            foreach ($exempt as $participant) {
                $fullName = trim($participant->first_name . ' ' . $participant->first_last_name);
                $individualPrice = number_format($participant->individual_price, 0, ',', '.');
                $discount = number_format($participant->discount, 0, ',', '.');
                $finalAmount = number_format($participant->final_amount, 0, ',', '.');
                
                echo "- ID: {$participant->id}\n";
                echo "  Nombre: {$fullName}\n";
                echo "  RUT: {$participant->document_number}\n";
                echo "  Email: {$participant->email}\n";
                echo "  Precio Individual: \${$individualPrice}\n";
                echo "  Descuento: \${$discount} ({$participant->discount_percentage}%)\n";
                echo "  Monto Final: \${$finalAmount}\n";
                echo "  Fuente del Descuento: {$participant->discount_source}\n";
                
                // Mostrar información adicional si es de participant_program_discounts
                if ($participant->discount_source === 'program_discount_released') {
                    echo "  Tipo de Descuento: {$participant->discount_type}\n";
                    if (!empty($participant->comment)) {
                        echo "  Comentario: {$participant->comment}\n";
                    }
                }
                
                echo "  ---\n";
            }
        }

        // Resumen final
        echo "\n=== RESUMEN ===\n";
        echo "Participantes sin ecommerce iniciado: " . $withoutInstallments->count() . "\n";
        echo "Participantes que terminaron de pagar: " . $fullyPaid->count() . "\n";
        echo "Participantes con descuento de liberado: " . $exempt->count() . "\n";
        echo "===============\n\n";
    }

    /**
     * Envía emails de aviso de no pago a los contactos de emergencia
     */
    private function sendNoPaymentEmails($participantsWithoutInstallments)
    {
        $emailsSent = 0;
        $emailsSkipped = 0;

        foreach ($participantsWithoutInstallments as $participant) {
            // Obtener contacto de emergencia
            $emergencyContact = $this->getEmergencyContact($participant->id);
            
            if (!$emergencyContact) {
                echo "⚠️ Participante ID {$participant->id} ({$participant->first_name} {$participant->first_last_name}) - Sin contacto de emergencia\n";
                $emailsSkipped++;
                continue;
            }

            // Obtener datos dinámicos del participante
            $participantData = $this->getParticipantEmailData($participant->id);
            
            if (!$participantData) {
                echo "⚠️ Participante ID {$participant->id} - No se pudieron obtener los datos del programa\n";
                $emailsSkipped++;
                continue;
            }

            // Simular envío de email (por ahora solo echo)
            $this->sendNoPaymentEmail($emergencyContact, $participantData, $participant);
            $emailsSent++;
        }

        echo "\n📊 Resumen de envío de emails:\n";
        echo "✅ Emails enviados: {$emailsSent}\n";
        echo "⚠️ Emails omitidos: {$emailsSkipped}\n";
        echo "📧 Total procesados: " . ($emailsSent + $emailsSkipped) . "\n\n";
    }

    /**
     * Obtiene el contacto de emergencia de un participante
     */
    private function getEmergencyContact($participantId)
    {
        return DB::table('emergency_contact')
            ->where('participant_id', $participantId)
            ->select('name', 'email', 'document_number', 'phone', 'relationship')
            ->first();
    }

    /**
     * Obtiene los datos dinámicos del participante para el email
     */
    private function getParticipantEmailData($participantId)
    {
        return DB::table('participants as p')
            ->join('participant_program as pp', 'p.id', '=', 'pp.participant_id')
            ->leftJoin('participant_program_discounts as ppd', 'pp.id', '=', 'ppd.participant_program_id')
            ->leftJoin('programs as prog', 'pp.program_id', '=', 'prog.id')
            ->select(
                'pp.created_at as incorporation_date',
                'pp.individual_price',
                'prog.name as program_name',
                DB::raw('COALESCE(ppd.amount, 0) as discount_amount'),
                DB::raw('COALESCE(ppd.percent, 0) as discount_percent'),
                DB::raw('CASE 
                    WHEN ppd.percent IS NOT NULL THEN pp.individual_price - (pp.individual_price * ppd.percent / 100)
                    WHEN ppd.amount IS NOT NULL THEN pp.individual_price - ppd.amount
                    ELSE pp.individual_price
                END as final_amount')
            )
            ->where('p.id', $participantId)
            ->first();
    }

    /**
     * Envía el email de no pago (por ahora solo echo)
     */
    private function sendNoPaymentEmail($emergencyContact, $participantData, $participant)
    {
        $incorporationDate = date('d/m/Y', strtotime($participantData->incorporation_date));
        $finalAmount = number_format($participantData->final_amount, 0, ',', '.');
        
        echo "📧 EMAIL ENVIADO:\n";
        echo "   Para: {$emergencyContact->email} ({$emergencyContact->name})\n";
        echo "   Participante: {$participant->first_name} {$participant->first_last_name}\n";
        echo "   RUT: {$participant->document_number}\n";
        echo "   Fecha incorporación: {$incorporationDate}\n";
        echo "   Monto programa: \${$finalAmount}\n";
        echo "   Programa: {$participantData->program_name}\n";
        
        if ($participantData->discount_amount > 0 || $participantData->discount_percent > 0) {
            $discountAmount = number_format($participantData->discount_amount, 0, ',', '.');
            echo "   Descuento aplicado: \${$discountAmount} ({$participantData->discount_percent}%)\n";
        }
        
        echo "   ---\n";

        // Aquí iría la lógica real de envío de email:
        /*
        Mail::send('Mails.no_payment', [
            'participant_name' => $participant->first_name . ' ' . $participant->first_last_name,
            'incorporation_date' => $incorporationDate,
            'program_amount' => $finalAmount,
            'program_name' => $participantData->program_name,
            'emergency_contact_name' => $emergencyContact->name
        ], function ($message) use ($emergencyContact, $participant) {
            $message->to($emergencyContact->email, $emergencyContact->name);
            $message->subject('Latitud 90 - Aviso situación Portal Pago "Programa educativo"');
        });
        */
    }
}