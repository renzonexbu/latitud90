<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contactData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $contactData)
    {
        $this->contactData = $contactData;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nuevo mensaje de contacto - Latitud 90',
            replyTo: (string) ($this->contactData['email'] ?? ''),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $processedData = [
            'name' => (string) ($this->contactData['name'] ?? ''),
            'email' => (string) ($this->contactData['email'] ?? ''),
            'phone' => (string) ($this->contactData['phone'] ?? ''),
            'contactMessage' => (string) ($this->contactData['message'] ?? ''),
        ];

        return new Content(
            view: 'emails.contact-message',
            with: $processedData,
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
