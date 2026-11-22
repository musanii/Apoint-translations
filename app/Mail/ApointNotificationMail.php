<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ApointNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public array $backoff = [60, 300, 900, 3600];

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

    public function failed(Throwable $exception): void
    {
        Log::error('Apoint notification email exhausted its queue retries.', [
            'category' => $this->category,
            'subject' => $this->title,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
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
