<?php

namespace App\Jobs;

use App\Mail\NoPaymentReminderMail;
use App\Mail\DailyReminderSummaryMail;
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
            'orders.payments.paymentOption',
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

                // Verificar si tiene pagos ecommerce completados para esta inscripción
                if ($this->hasEcommercePayments($enrollment)) {
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
                $programCode = $enrollment->programCourse?->code ?? 'N/A';

                // Obtener monto del programa
                $programAmount = $enrollment->individual_price ?? 0;
                $programAmountFormatted = number_format($programAmount, 0, ',', '.');

                // Datos para el email
                $participantName = $participant->full_name;
                $enrollmentDateFormatted = $enrollmentDate->format('d/m/Y');
                $enrollmentCode = $enrollment->enrollment_code ?? '-';

                // Agregar al resumen
                $summaryData[] = [
                    'id' => $participant->id,
                    'enrollment_code' => $enrollmentCode,
                    'name' => $participantName,
                    'email' => $participant->email,
                    'guardian_email' => $emergencyContact->email,
                    'guardian_name' => $emergencyContact->name,
                    'program' => $programName,
                    'program_code' => $programCode,
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
     * Verificar si la inscripción tiene pagos ecommerce completados
     *
     * NOTA: Usamos la relación directa ParticipantProgram->orders->payments
     * para evitar el desajuste de IDs entre program_id de ParticipantProgram
     * (que apunta a program_courses) y program_id de Order.
     */
    protected function hasEcommercePayments(ParticipantProgram $enrollment): bool
    {
        return $enrollment->orders()
            ->whereHas('payments', function ($paymentQuery) {
                $paymentQuery->whereIn('status', ['completed', 'approved', 'paid'])
                    ->whereHas('paymentOption', function ($optionQuery) {
                        // Solo pagos ecommerce (excluir manuales/presenciales)
                        $optionQuery->where(function ($q) {
                            $q->where('code', 'like', 'full_%')
                              ->orWhere('code', 'like', 'subscription_%');
                        });
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
     * Enviar resumen diario a los administradores usando plantilla con branding
     */
    protected function sendAdminSummary(
        array $summaryData,
        int $remindersSent,
        int $remindersSkipped,
        int $notDueYet
    ): void {
        $today = Carbon::now('America/Santiago')->format('d/m/Y');

        // Ordenar por días desde enrolamiento (mayor a menor)
        usort($summaryData, fn($a, $b) => $b['days_since_enrollment'] <=> $a['days_since_enrollment']);

        try {
            Mail::to($this->adminEmails)->send(
                new DailyReminderSummaryMail(
                    summaryData: $summaryData,
                    remindersSent: $remindersSent,
                    remindersSkipped: $remindersSkipped,
                    notDueYet: $notDueYet,
                    date: $today
                )
            );

            Log::info('SendNoPaymentReminders: Resumen enviado a administradores');

        } catch (\Exception $e) {
            Log::error('SendNoPaymentReminders: Error enviando resumen', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
