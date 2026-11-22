<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApointNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $title,
        public readonly string $message,
        public readonly string $actionUrl,
        public readonly string $category,
    ) {
        $this->afterCommit();
    }

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
                'message' => $this->message,
                'actionUrl' => $this->actionUrl,
                'category' => $this->category,
            ],
        );
    }
}
