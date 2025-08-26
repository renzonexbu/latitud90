<?php

namespace App\Services\Client;

use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessageMail;
use App\Traits\SystemLogging;

class ContactMessageService
{
    use SystemLogging;
    /**
     * Enviar mensaje de contacto
     */
    public function sendContactMessage(array $data)
    {
        try {
            // Email de destino (tu email)
            $adminEmail = config('mail.contact.admin_email', 'admin@latitud90.com');

            // Enviar email con Reply-To configurado
            Mail::to($adminEmail)
                ->send(new ContactMessageMail($data));

            return [
                'success' => true,
                'message' => 'Mensaje enviado correctamente. Te responderemos pronto.'
            ];
        } catch (\Exception $e) {
            $this->logError('Error enviando mensaje de contacto: ' . $e->getMessage(), [], $e);

            return [
                'success' => false,
                'message' => 'Error al enviar el mensaje. Por favor, intenta nuevamente.'
            ];
        }
    }
}
