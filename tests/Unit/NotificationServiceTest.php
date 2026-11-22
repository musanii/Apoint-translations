<?php

use App\Mail\ApointNotificationMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;

uses(DatabaseMigrations::class);

it('stores an in-app notification and queues its branded email after commit', function () {
    Mail::fake();
    $user = User::factory()->create();

    app(NotificationService::class)->user(
        $user,
        'Your quotation is ready',
        'Please review your quotation.',
        'https://apoint.example/quotes/123',
        'quote',
        true,
    );

    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
    ]);

    Mail::assertQueued(ApointNotificationMail::class, fn (ApointNotificationMail $mail): bool => (
        $mail->title === 'Your quotation is ready'
        && $mail->recipientName === $user->name
        && $mail->actionUrl === 'https://apoint.example/quotes/123'
    ));
});

it('notifies every administrator without sending admin alerts to customers', function () {
    Mail::fake();

    $firstAdmin = User::factory()->create();
    $firstAdmin->forceFill(['is_admin' => true])->save();
    $secondAdmin = User::factory()->create();
    $secondAdmin->forceFill(['is_admin' => true])->save();
    $customer = User::factory()->create();

    app(NotificationService::class)->admins(
        'New quote request',
        'A customer submitted a request.',
        'https://apoint.example/admin/quote-requests/1',
        'quote_request',
        true,
    );

    $this->assertDatabaseCount('notifications', 2);
    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $firstAdmin->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $secondAdmin->id,
    ]);
    $this->assertDatabaseMissing('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $customer->id,
    ]);

    Mail::assertQueued(ApointNotificationMail::class, 2);
});
