<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyReminderSummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public array $summaryData,
        public int $remindersSent,
        public int $remindersSkipped,
        public int $notDueYet,
        public string $date
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recordatorios de Pago: {$this->remindersSent} enviados - {$this->date}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $totalParticipants = count($this->summaryData);
        $totalAmount = array_sum(array_column($this->summaryData, 'amount'));

        return new Content(
            view: 'Mails.daily_reminder_summary',
            with: [
                'summaryData' => $this->summaryData,
                'remindersSent' => $this->remindersSent,
                'remindersSkipped' => $this->remindersSkipped,
                'notDueYet' => $this->notDueYet,
                'totalParticipants' => $totalParticipants,
                'totalAmount' => $totalAmount,
                'date' => $this->date,
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
