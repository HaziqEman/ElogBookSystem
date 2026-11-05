<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ReminderMail extends Mailable
{
    /**
     * @param  array<int, array{title: string, detail?: string|null}>  $items
     */
    public function __construct(
        public string $recipientName,
        public string $mailSubject,
        public string $intro,
        public array $items,
        public string $link,
        public string $buttonText = 'Open Smart eLogBook',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.reminder');
    }
}
