<?php

namespace App\Jobs;

use App\Mail\NoPaymentReminderMail;
use App\Models\Participant;
use App\Models\ParticipantProgram;
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
     * Emails administrativos para el resumen diario
     */
    protected array $adminEmails = [
        'yohan@nexbu.com',
        'pagos@latitud90.com',
        'ccampillay@latitud90.com',
    ];

    /**
     * Intervalo de días entre recordatorios
     */
    protected int $reminderIntervalDays = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * Lógica:
     * 1. Obtener participantes activos sin pagos ecommerce completados
     * 2. Para cada uno, verificar si hoy es múltiplo de 30 días desde su fecha de enrolamiento
     * 3. Enviar recordatorio al email del apoderado (contacto de emergencia)
     * 4. Si no tiene email de apoderado, saltar al siguiente
     */
    public function handle(): void
    {
        $today = Carbon::today('America/Santiago');

        Log::info('SendNoPaymentReminders: Iniciando proceso', [
            'fecha' => $today->format('Y-m-d'),
            'intervalo_dias' => $this->reminderIntervalDays
        ]);

        // Obtener participantes con inscripciones a programas activos
        // que no tienen pagos ecommerce completados
        $participantPrograms = ParticipantProgram::with([
            'participant.emergencyContacts',
            'participant.payments',
            'programCourse.program'
        ])
            ->whereHas('participant', function ($query) {
                $query->where('is_active', true);
            })
            ->where('is_active', true)
            ->get();

        Log::info('SendNoPaymentReminders: Inscripciones encontradas', [
            'total' => $participantPrograms->count()
        ]);

        $summaryData = [];
        $remindersSent = 0;
        $remindersSkipped = 0;
        $notDueYet = 0;

        foreach ($participantPrograms as $enrollment) {
            try {
                $participant = $enrollment->participant;

                if (!$participant) {
                    continue;
                }

                // Verificar si tiene pagos ecommerce completados para este programa
                if ($this->hasEcommercePayments($participant, $enrollment->program_id)) {
                    continue;
                }

                // Calcular días desde el enrolamiento
                $enrollmentDate = Carbon::parse($enrollment->created_at)->startOfDay();
                $daysSinceEnrollment = $enrollmentDate->diffInDays($today);

                // Verificar si hoy corresponde enviar recordatorio (múltiplo de 30 días)
                if (!$this->shouldSendReminder($daysSinceEnrollment)) {
                    $notDueYet++;
                    continue;
                }

                // Obtener email del apoderado (contacto de emergencia)
                $emergencyContact = $participant->emergencyContacts->first();

                if (!$emergencyContact || empty($emergencyContact->email)) {
                    Log::info("SendNoPaymentReminders: Participante {$participant->id} sin email de apoderado, saltando");
                    $remindersSkipped++;
                    continue;
                }

                // Obtener datos del programa
                $programName = $enrollment->programCourse?->name ??
                               $enrollment->programCourse?->program?->name ?? 'N/A';

                // Obtener monto del programa
                $programAmount = $enrollment->individual_price ?? 0;
                $programAmountFormatted = number_format($programAmount, 0, ',', '.');

                // Datos para el email
                $participantName = $participant->full_name;
                $enrollmentDateFormatted = $enrollmentDate->format('d/m/Y');

                // Agregar al resumen
                $summaryData[] = [
                    'id' => $participant->id,
                    'name' => $participantName,
                    'email' => $participant->email,
                    'guardian_email' => $emergencyContact->email,
                    'guardian_name' => $emergencyContact->name,
                    'program' => $programName,
                    'amount' => $programAmount,
                    'amount_formatted' => $programAmountFormatted,
                    'enrollment_date' => $enrollmentDateFormatted,
                    'days_since_enrollment' => $daysSinceEnrollment,
                    'reminder_number' => floor($daysSinceEnrollment / $this->reminderIntervalDays),
                ];

                // Enviar email al apoderado
                try {
                    Mail::to($emergencyContact->email)->send(
                        new NoPaymentReminderMail(
                            $participantName,
                            $enrollmentDateFormatted,
                            $programAmountFormatted,
                            $programName
                        )
                    );

                    Log::info("SendNoPaymentReminders: Recordatorio enviado", [
                        'participant_id' => $participant->id,
                        'guardian_email' => $emergencyContact->email,
                        'days_since_enrollment' => $daysSinceEnrollment,
                        'reminder_number' => floor($daysSinceEnrollment / $this->reminderIntervalDays)
                    ]);

                    $remindersSent++;

                } catch (\Exception $mailError) {
                    Log::warning("SendNoPaymentReminders: Error enviando email", [
                        'guardian_email' => $emergencyContact->email,
                        'error' => $mailError->getMessage()
                    ]);
                }

            } catch (\Exception $e) {
                Log::error("SendNoPaymentReminders: Error procesando inscripción", [
                    'enrollment_id' => $enrollment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Enviar resumen a administradores
        $this->sendAdminSummary($summaryData, $remindersSent, $remindersSkipped, $notDueYet);

        Log::info('SendNoPaymentReminders: Job completado', [
            'enviados' => $remindersSent,
            'omitidos_sin_email' => $remindersSkipped,
            'no_corresponde_hoy' => $notDueYet
        ]);
    }

    /**
     * Verificar si el participante tiene pagos ecommerce para un programa específico
     */
    protected function hasEcommercePayments(Participant $participant, int $programId): bool
    {
        return $participant->payments()
            ->where('status', 'completed')
            ->whereHas('order', function ($query) use ($programId) {
                $query->where('program_id', $programId);
            })
            ->whereHas('paymentOption', function ($query) {
                // Solo pagos ecommerce (excluir manuales/presenciales)
                $query->where(function ($q) {
                    $q->where('code', 'like', 'full_%')
                      ->orWhere('code', 'like', 'subscription_%');
                });
            })
            ->exists();
    }

    /**
     * Verificar si corresponde enviar recordatorio hoy
     * Se envía si los días desde enrolamiento son múltiplo de 30 (30, 60, 90, etc.)
     */
    protected function shouldSendReminder(int $daysSinceEnrollment): bool
    {
        // No enviar si es el día 0 (mismo día del enrolamiento)
        if ($daysSinceEnrollment < $this->reminderIntervalDays) {
            return false;
        }

        // Verificar si es múltiplo exacto del intervalo
        return $daysSinceEnrollment % $this->reminderIntervalDays === 0;
    }

    /**
     * Enviar resumen diario a los administradores
     */
    protected function sendAdminSummary(
        array $summaryData,
        int $remindersSent,
        int $remindersSkipped,
        int $notDueYet
    ): void {
        $totalParticipants = count($summaryData);
        $totalAmount = array_sum(array_column($summaryData, 'amount'));
        $today = Carbon::now('America/Santiago')->format('d/m/Y');

        $content = "RESUMEN DIARIO - RECORDATORIOS DE PAGO (CADA 30 DÍAS DESDE ENROLAMIENTO)\n";
        $content .= "Fecha: {$today}\n";
        $content .= str_repeat("=", 70) . "\n\n";

        $content .= "ESTADISTICAS:\n";
        $content .= "- Recordatorios enviados hoy: {$remindersSent}\n";
        $content .= "- Omitidos (sin email de apoderado): {$remindersSkipped}\n";
        $content .= "- No correspondía hoy (fuera de ciclo 30 días): {$notDueYet}\n";
        $content .= "- Monto total pendiente (enviados): $" . number_format($totalAmount, 0, ',', '.') . "\n";
        $content .= str_repeat("-", 70) . "\n\n";

        if ($totalParticipants > 0) {
            $content .= "DETALLE DE RECORDATORIOS ENVIADOS:\n\n";

            // Ordenar por días desde enrolamiento (mayor a menor)
            usort($summaryData, fn($a, $b) => $b['days_since_enrollment'] <=> $a['days_since_enrollment']);

            foreach ($summaryData as $index => $data) {
                $num = $index + 1;
                $content .= "{$num}. {$data['name']}\n";
                $content .= "   Programa: {$data['program']}\n";
                $content .= "   Monto: \${$data['amount_formatted']}\n";
                $content .= "   Fecha enrolamiento: {$data['enrollment_date']}\n";
                $content .= "   Días sin pago: {$data['days_since_enrollment']} días\n";
                $content .= "   Recordatorio #: {$data['reminder_number']}\n";
                $content .= "   Email apoderado: {$data['guardian_email']} ({$data['guardian_name']})\n";
                $content .= "\n";
            }
        } else {
            $content .= "No hubo recordatorios que enviar hoy.\n";
            $content .= "(Los recordatorios se envían cada 30 días desde la fecha de enrolamiento)\n";
        }

        $content .= str_repeat("=", 70) . "\n";
        $content .= "Este es un correo automático generado por el sistema.\n";

        try {
            Mail::raw($content, function ($message) use ($today, $remindersSent) {
                $message->to($this->adminEmails)
                    ->subject("Recordatorios de Pago: {$remindersSent} enviados - {$today}");
            });

            Log::info('SendNoPaymentReminders: Resumen enviado a administradores');

        } catch (\Exception $e) {
            Log::error('SendNoPaymentReminders: Error enviando resumen', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
