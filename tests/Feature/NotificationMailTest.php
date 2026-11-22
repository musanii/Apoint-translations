<?php

use App\Mail\ApointNotificationMail;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the branded Apoint email with the intended action link', function () {
    $url = route('dashboard');
    $mail = new ApointNotificationMail(
        recipientName: 'Test Customer',
        title: 'Your quotation is ready',
        bodyText: 'Please review your quotation.',
        actionUrl: $url,
        category: 'quote',
    );

    $html = $mail->render();

    expect($mail->envelope()->subject)->toBe('Your quotation is ready | Apoint Translations')
        ->and($html)->toContain('Apoint Translations')
        ->and($html)->toContain('#3A091A')
        ->and($html)->toContain('Test Customer')
        ->and($html)->toContain('Your quotation is ready')
        ->and($html)->toContain(e($url));
});
