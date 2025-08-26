<?php

namespace App\Services\Client\Communication;

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
            // Validar y convertir datos a cadenas de texto
            $validatedData = [
                'name' => (string) ($data['name'] ?? ''),
                'email' => (string) ($data['email'] ?? ''),
                'phone' => (string) ($data['phone'] ?? ''),
                'message' => (string) ($data['message'] ?? ''),
            ];

            // Email de destino (tu email)
            $adminEmail = 'admin@test.com'; // Email temporal para pruebas

            // Enviar email con Reply-To configurado
            Mail::to($adminEmail)
                ->send(new ContactMessageMail($validatedData));

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
