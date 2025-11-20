<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FailedPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $participantName,
        public int $installmentNumber,
        public string $amount,
        public string $chargeDate,
        public string $cardUpdateLink,
        public string $programName
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Latitud 90 - Cobro Rechazado - Acción Requerida',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Mails.failed_payment',
            with: [
                'participant_name' => $this->participantName,
                'installment_number' => $this->installmentNumber,
                'amount' => $this->amount,
                'charge_date' => $this->chargeDate,
                'card_update_link' => $this->cardUpdateLink,
                'program_name' => $this->programName,
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
