<?php

namespace App\Mail;

use App\Models\GuardianUser;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuardianPasswordReset extends Mailable
{
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public GuardianUser $user,
        public string $token
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de acceso a tu cuenta - Latitud90',
            replyTo: [
                new \Illuminate\Mail\Mailables\Address(config('lat90.company.email', 'contacto@latitud90.com'), 'Soporte Latitud 90'),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $resetUrl = url("/guardian/account-access/{$this->user->id}/{$this->token}");

        return new Content(
            view: 'emails.guardian.reset-password',
            with: [
                'user' => $this->user,
                'resetUrl' => $resetUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
