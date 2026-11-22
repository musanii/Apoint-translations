<?php

use App\Http\Controllers\Admin\QuoteDraftController;
use App\Mail\ApointNotificationMail;
use App\Models\Quote;
use App\Services\NotificationService;
use App\Services\QuoteStatusService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;

uses(DatabaseMigrations::class);

it('sends the customer a branded email when an admin publishes a quotation', function () {
    Mail::fake();

    $quote = Quote::factory()->create();
    $customer = $quote->quoteRequest->user;

    app(QuoteDraftController::class)->send(
        $quote,
        app(QuoteStatusService::class),
        app(NotificationService::class),
    );

    expect($quote->fresh()->status)->toBe(Quote::STATUS_SENT)
        ->and($quote->quoteRequest->fresh()->status)->toBe('quote_sent');

    Mail::assertSent(ApointNotificationMail::class, fn (ApointNotificationMail $mail): bool => (
        $mail->title === 'Your quotation is ready'
        && $mail->recipientName === $customer->name
        && $mail->actionUrl === route('quotes.show', $quote)
    ));
});
