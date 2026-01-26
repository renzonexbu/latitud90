<?php

namespace App\Jobs;

use App\Mail\NoPaymentReminderMail;
use App\Models\Participant;
use App\Models\InstallmentPlan;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNoPaymentReminders implements ShouldQueue
{
    use Queueable;

    /**
     * Emails administrativos para el resumen diario
     */
    protected array $adminEmails = [
        'yohan@nexbu.com',
        'pagos@latitud90.com',
        'ccampillay@latitud90.com',
    ];

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Obtener participantes que:
        // 1. No tienen ningún pago completado POR ECOMMERCE (pasarela)
        //    - Pagos presenciales/manuales (gateway_code like 'manual_%') NO se consideran
        // 2. No han recibido recordatorio en los últimos 30 días (o nunca)
        // 3. Están activos

        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $participants = Participant::where('is_active', true)
            ->where(function($query) use ($thirtyDaysAgo) {
                $query->whereNull('last_no_payment_reminder_sent_at')
                      ->orWhere('last_no_payment_reminder_sent_at', '<=', $thirtyDaysAgo);
            })
            ->whereDoesntHave('payments', function($query) {
                // Solo considerar pagos por ecommerce (excluir manuales/presenciales)
                $query->where('status', 'completed')
                      ->whereHas('paymentOption', function($q) {
                          $q->where('gateway_code', 'not like', 'manual_%');
                      });
            })
            ->with(['emergencyContacts', 'orders.programCourse.program'])
            ->get();

        Log::info('SendNoPaymentReminders: Procesando ' . $participants->count() . ' participantes');

        // Recopilar datos para el resumen
        $summaryData = [];
        $remindersSent = 0;
        $remindersSkipped = 0;

        foreach ($participants as $participant) {
            try {
                // Obtener contactos de emergencia
                $emergencyContacts = $participant->emergencyContacts;

                // Obtener el plan de cuotas para obtener el monto del programa
                $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $programAmount = $installmentPlan ? $installmentPlan->total_amount : 0;
                $programAmountFormatted = $installmentPlan ? number_format($installmentPlan->total_amount, 0, ',', '.') : 'N/A';

                // Obtener programa del participante
                $programName = 'N/A';
                $latestOrder = $participant->orders()->latest()->first();
                if ($latestOrder && $latestOrder->programCourse) {
                    $programName = $latestOrder->programCourse->program->name ?? $latestOrder->programCourse->name ?? 'N/A';
                }

                // Datos para el email
                $participantName = $participant->full_name;
                $incorporationDate = $participant->created_at->format('d/m/Y');

                // Agregar al resumen
                $summaryData[] = [
                    'id' => $participant->id,
                    'name' => $participantName,
                    'email' => $participant->email,
                    'program' => $programName,
                    'amount' => $programAmount,
                    'amount_formatted' => $programAmountFormatted,
                    'incorporation_date' => $incorporationDate,
                    'days_without_payment' => $participant->created_at->diffInDays(Carbon::now()),
                ];

                if ($emergencyContacts->isEmpty()) {
                    Log::warning("Participante {$participant->id} no tiene contactos de emergencia");
                    $remindersSkipped++;
                    continue;
                }

                // Enviar email a cada contacto de emergencia
                foreach ($emergencyContacts as $contact) {
                    if (empty($contact->email)) {
                        Log::warning("Contacto de emergencia {$contact->id} no tiene email");
                        continue;
                    }

                    try {
                        Mail::to($contact->email)->send(
                            new NoPaymentReminderMail(
                                $participantName,
                                $incorporationDate,
                                $programAmountFormatted
                            )
                        );
                        Log::info("Recordatorio enviado a {$contact->email} para participante {$participant->id}");
                        $remindersSent++;
                    } catch (\Exception $mailError) {
                        Log::warning("No se pudo enviar recordatorio a {$contact->email}: " . $mailError->getMessage());
                    }
                }

                // Actualizar fecha del último recordatorio
                $participant->update([
                    'last_no_payment_reminder_sent_at' => Carbon::now()
                ]);

            } catch (\Exception $e) {
                Log::error("Error enviando recordatorio para participante {$participant->id}: " . $e->getMessage());
            }
        }

        // Enviar resumen diario a administradores
        $this->sendAdminSummary($summaryData, $remindersSent, $remindersSkipped);

        Log::info('SendNoPaymentReminders: Job completado');
    }

    /**
     * Enviar resumen diario a los administradores
     */
    protected function sendAdminSummary(array $summaryData, int $remindersSent, int $remindersSkipped): void
    {
        $totalParticipants = count($summaryData);
        $totalAmount = array_sum(array_column($summaryData, 'amount'));
        $today = Carbon::now()->format('d/m/Y');

        // Construir el contenido del email
        $content = "RESUMEN DIARIO - PARTICIPANTES SIN PAGOS POR ECOMMERCE\n";
        $content .= "Fecha: {$today}\n";
        $content .= str_repeat("=", 60) . "\n\n";

        $content .= "ESTADISTICAS:\n";
        $content .= "- Total participantes sin pago ecommerce: {$totalParticipants}\n";
        $content .= "- Monto total pendiente: $" . number_format($totalAmount, 0, ',', '.') . "\n";
        $content .= "- Recordatorios enviados hoy: {$remindersSent}\n";
        $content .= "- Omitidos (sin contacto de emergencia): {$remindersSkipped}\n";
        $content .= str_repeat("-", 60) . "\n\n";

        if ($totalParticipants > 0) {
            $content .= "DETALLE DE PARTICIPANTES:\n\n";

            // Ordenar por dias sin pago (mayor a menor)
            usort($summaryData, fn($a, $b) => $b['days_without_payment'] <=> $a['days_without_payment']);

            foreach ($summaryData as $index => $participant) {
                $num = $index + 1;
                $content .= "{$num}. {$participant['name']}\n";
                $content .= "   Email: {$participant['email']}\n";
                $content .= "   Programa: {$participant['program']}\n";
                $content .= "   Monto: \${$participant['amount_formatted']}\n";
                $content .= "   Fecha inscripcion: {$participant['incorporation_date']}\n";
                $content .= "   Dias sin pago: {$participant['days_without_payment']} dias\n";
                $content .= "\n";
            }
        } else {
            $content .= "No hay participantes sin pagos por ecommerce pendientes.\n";
        }

        $content .= str_repeat("=", 60) . "\n";
        $content .= "Este es un correo automatico generado por el sistema.\n";

        try {
            Mail::raw($content, function ($message) use ($today, $totalParticipants) {
                $message->to($this->adminEmails)
                    ->subject("Resumen Diario: {$totalParticipants} participantes sin pago ecommerce - {$today}");
            });

            Log::info('SendNoPaymentReminders: Resumen enviado a administradores', [
                'emails' => $this->adminEmails,
                'total_participants' => $totalParticipants,
            ]);
        } catch (\Exception $e) {
            Log::error('SendNoPaymentReminders: Error enviando resumen a administradores', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
