<?php

use App\Mail\ApointNotificationMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->boolean('is_admin')->default(false);
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('notifications', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('type');
        $table->morphs('notifiable');
        $table->text('data');
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('users');
});

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

    Mail::assertQueuedCount(2);
});
