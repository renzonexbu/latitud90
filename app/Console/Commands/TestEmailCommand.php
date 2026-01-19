<?php

namespace App\Console\Commands;

use App\Mail\TestEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test
                            {--to=* : Email adicional para enviar (opcional)}
                            {--html : Enviar con plantilla HTML (por defecto es texto plano)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía un correo de prueba para verificar la configuración de email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $useHtml = $this->option('html');
        $format = $useHtml ? 'HTML' : 'Texto plano';

        $this->info('====================================');
        $this->info('Enviando correos de prueba...');
        $this->info("Formato: {$format}");
        $this->info('====================================');
        $this->newLine();

        // Emails por defecto
        $defaultEmails = [
            'yohan@nexbu.com',
            'yohan.0055@gmail.com'
        ];

        // Agregar emails adicionales si se proporcionan
        $additionalEmails = $this->option('to');
        $emails = array_merge($defaultEmails, $additionalEmails);

        // Información del correo
        $subject = 'Prueba de Configuración de Email - Latitud 90';
        $appName = config('app.name');
        $appUrl = config('app.url');
        $appEnv = config('app.env');
        $mailDriver = config('mail.default');
        $mailHost = config('mail.mailers.smtp.host');
        $sentAt = now()->format('Y-m-d H:i:s');

        $successCount = 0;
        $failedCount = 0;

        foreach ($emails as $email) {
            $this->line("Enviando a: <fg=cyan>{$email}</>... ");

            try {
                if ($useHtml) {
                    // Enviar con plantilla HTML
                    Mail::to($email)->send(new TestEmail());
                } else {
                    // Enviar como texto plano
                    Mail::raw(
                        "Este es un correo de prueba del sistema {$appName}\n\n" .
                        "Detalles de la configuración:\n" .
                        "- Aplicación: {$appName}\n" .
                        "- URL: {$appUrl}\n" .
                        "- Entorno: {$appEnv}\n" .
                        "- Driver de email: {$mailDriver}\n" .
                        "- Host SMTP: {$mailHost}\n" .
                        "- Enviado el: {$sentAt}\n\n" .
                        "Si recibes este correo, la configuración de email está funcionando correctamente.\n\n" .
                        "---\n" .
                        "Este es un mensaje automático generado por el comando: php artisan email:test",
                        function ($message) use ($email, $subject) {
                            $message->to($email)
                                ->subject($subject);
                        }
                    );
                }

                $this->info("  ✓ Enviado exitosamente");
                $successCount++;
            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
                $failedCount++;
            }
        }

        $this->newLine();
        $this->info('====================================');
        $this->info('Resumen:');
        $this->line("  Total de correos enviados: <fg=green>{$successCount}</>");

        if ($failedCount > 0) {
            $this->line("  Total de correos fallidos: <fg=red>{$failedCount}</>");
        }

        $this->info('====================================');

        if ($successCount > 0 && $failedCount === 0) {
            $this->newLine();
            $this->info('✓ Todos los correos fueron enviados exitosamente.');
            $this->info('  Revisa tu bandeja de entrada y carpeta de spam.');
            return Command::SUCCESS;
        } elseif ($failedCount > 0) {
            $this->newLine();
            $this->warn('⚠ Algunos correos no pudieron ser enviados.');
            $this->warn('  Verifica la configuración de email en el archivo .env');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
