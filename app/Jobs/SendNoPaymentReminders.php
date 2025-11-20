<?php

namespace App\Jobs;

use App\Mail\NoPaymentReminderMail;
use App\Models\Participant;
use App\Models\InstallmentPlan;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendNoPaymentReminders implements ShouldQueue
{
    use Queueable;

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
        // 1. No tienen ningún pago completado
        // 2. No han recibido recordatorio en los últimos 30 días (o nunca)
        // 3. Están activos

        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $participants = Participant::where('is_active', true)
            ->where(function($query) use ($thirtyDaysAgo) {
                $query->whereNull('last_no_payment_reminder_sent_at')
                      ->orWhere('last_no_payment_reminder_sent_at', '<=', $thirtyDaysAgo);
            })
            ->whereDoesntHave('payments', function($query) {
                $query->where('status', 'completed');
            })
            ->with(['emergencyContacts'])
            ->get();

        Log::info('SendNoPaymentReminders: Procesando ' . $participants->count() . ' participantes');

        foreach ($participants as $participant) {
            try {
                // Obtener contactos de emergencia
                $emergencyContacts = $participant->emergencyContacts;

                if ($emergencyContacts->isEmpty()) {
                    Log::warning("Participante {$participant->id} no tiene contactos de emergencia");
                    continue;
                }

                // Obtener el plan de cuotas para obtener el monto del programa
                $installmentPlan = InstallmentPlan::where('participant_id', $participant->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $programAmount = $installmentPlan ? number_format($installmentPlan->total_amount, 0, ',', '.') : 'N/A';

                // Datos para el email
                $participantName = $participant->full_name;
                $incorporationDate = $participant->created_at->format('d/m/Y');

                // Enviar email a cada contacto de emergencia
                foreach ($emergencyContacts as $contact) {
                    if (empty($contact->email)) {
                        Log::warning("Contacto de emergencia {$contact->id} no tiene email");
                        continue;
                    }

                    Mail::to($contact->email)->send(
                        new NoPaymentReminderMail(
                            $participantName,
                            $incorporationDate,
                            $programAmount
                        )
                    );

                    Log::info("Recordatorio enviado a {$contact->email} para participante {$participant->id}");
                }

                // Actualizar fecha del último recordatorio
                $participant->update([
                    'last_no_payment_reminder_sent_at' => Carbon::now()
                ]);

            } catch (\Exception $e) {
                Log::error("Error enviando recordatorio para participante {$participant->id}: " . $e->getMessage());
            }
        }

        Log::info('SendNoPaymentReminders: Job completado');
    }
}
