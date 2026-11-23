<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ApointNotificationMail extends Mailable
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $title,
        public readonly string $bodyText,
        public readonly string $actionUrl,
        public readonly string $category,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title.' | Apoint Translations');
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notification',
            with: [
                'recipientName' => $this->recipientName,
                'title' => $this->title,
                'bodyText' => $this->bodyText,
                'actionUrl' => $this->actionUrl,
                'category' => $this->category,
            ],
        );
    }
}
