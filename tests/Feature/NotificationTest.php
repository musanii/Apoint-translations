<?php

use App\Models\User;
use App\Notifications\ApointNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores an in-app notification for the authenticated user', function () {
    $user = User::factory()->create();

    $user->notify(new ApointNotification(
        title: 'Your quotation is ready',
        message: 'Your quotation is ready for review.',
        url: route('dashboard'),
        category: 'quote',
    ));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unreadCount', 1)
            ->where('notifications.items.0.data.title', 'Your quotation is ready')
        );
});

it('marks an owned notification as read and redirects to its internal destination', function () {
    $user = User::factory()->create();

    $user->notify(new ApointNotification(
        title: 'Order update',
        message: 'Your order has been updated.',
        url: route('dashboard'),
        category: 'order',
    ));

    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('dashboard'));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('does not allow a user to read another users notification', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $owner->notify(new ApointNotification(
        title: 'Private update',
        message: 'This belongs to another user.',
        url: route('dashboard'),
        category: 'order',
    ));

    $notification = $owner->notifications()->firstOrFail();

    $this->actingAs($otherUser)
        ->post(route('notifications.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('marks all unread notifications as read for only the authenticated user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $user->notify(new ApointNotification('First', 'First message', route('dashboard'), 'order'));
    $user->notify(new ApointNotification('Second', 'Second message', route('dashboard'), 'payment'));
    $otherUser->notify(new ApointNotification('Other', 'Other message', route('dashboard'), 'order'));

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($otherUser->unreadNotifications()->count())->toBe(1);
});

it('falls back to the dashboard for an external notification destination', function () {
    $user = User::factory()->create();

    $user->notify(new ApointNotification(
        title: 'Unsafe link',
        message: 'This notification has an external URL.',
        url: 'https://example.com/phishing',
        category: 'security',
    ));

    $notification = $user->notifications()->firstOrFail();

    $this->actingAs($user)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('dashboard'));
});
