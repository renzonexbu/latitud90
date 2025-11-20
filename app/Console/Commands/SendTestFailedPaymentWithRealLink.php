<?php

namespace App\Console\Commands;

use App\Mail\FailedPaymentMail;
use App\Models\ProgramSubscription;
use App\Services\Subscription\VirtualPosSubscriptionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestFailedPaymentWithRealLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-failed-payment-real {email} {--subscription_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar un email de cobro rechazado con link real de cambio de tarjeta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $subscriptionId = $this->option('subscription_id');

        // Obtener suscripción
        if ($subscriptionId) {
            $subscription = ProgramSubscription::find($subscriptionId);
        } else {
            $subscription = ProgramSubscription::where('status', 'ACTIVA')
                ->whereNotNull('virtualpos_subscription_id')
                ->with('participant', 'program')
                ->first();
        }

        if (!$subscription) {
            $this->error('❌ No se encontró una suscripción válida');
            return Command::FAILURE;
        }

        $this->info("📋 Usando suscripción ID: {$subscription->id}");
        $this->line("   Participante: {$subscription->participant->full_name}");
        $this->line("   VirtualPOS ID: {$subscription->virtualpos_subscription_id}");

        // Generar link de cambio de tarjeta
        $this->info("\n🔗 Generando link de cambio de tarjeta...");

        try {
            $virtualPosService = app(VirtualPosSubscriptionService::class);
            $response = $virtualPosService->generateCardChangeLink($subscription->virtualpos_subscription_id);

            $this->line("Respuesta de la API:");
            $this->line(json_encode($response, JSON_PRETTY_PRINT));

            if ($response && isset($response['change_card_url'])) {
                $cardUpdateLink = $response['change_card_url'];

                // Guardar el link en la suscripción
                $subscription->update([
                    'card_change_link' => $cardUpdateLink,
                    'card_change_link_generated_at' => Carbon::now()
                ]);

                $this->info("✅ Link generado exitosamente:");
                $this->line("   {$cardUpdateLink}");
            } else {
                $this->error('❌ No se pudo generar el link. Usando link de prueba.');
                $this->line("Campos disponibles en la respuesta: " . implode(', ', array_keys($response ?? [])));
                $cardUpdateLink = "https://portal.virtualpos.cl/card-update/test123";
            }
        } catch (\Exception $e) {
            $this->error("❌ Error generando link: " . $e->getMessage());
            $this->line("Usando link de prueba...");
            $cardUpdateLink = "https://portal.virtualpos.cl/card-update/test123";
        }

        // Preparar datos para el email
        $participantName = $subscription->participant->full_name;
        $installmentNumber = 3;
        $amount = number_format($subscription->amount, 0, ',', '.');
        $chargeDate = Carbon::now()->format('d/m/Y');
        $programName = $subscription->program->name ?? 'Programa';

        // Enviar email
        $this->info("\n📧 Enviando email de cobro rechazado a: {$email}");

        try {
            Mail::to($email)->send(
                new FailedPaymentMail(
                    $participantName,
                    $installmentNumber,
                    $amount,
                    $chargeDate,
                    $cardUpdateLink,
                    $programName
                )
            );

            $this->info("✅ Email enviado exitosamente a: {$email}");
            $this->line("\nDatos usados:");
            $this->line("  - Participante: {$participantName}");
            $this->line("  - Programa: {$programName}");
            $this->line("  - Cuota rechazada: {$installmentNumber}");
            $this->line("  - Monto: \${$amount}");
            $this->line("  - Fecha de intento: {$chargeDate}");
            $this->line("  - Link de actualización: {$cardUpdateLink}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error enviando email: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
