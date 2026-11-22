<?php

use App\Mail\ApointNotificationMail;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;

uses(DatabaseMigrations::class);

it('notifies the customer when fulfilment starts and emails them when it completes', function () {
    Mail::fake();

    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);
    $customer = User::query()->findOrFail($order->user_id);
    $service = app(OrderStatusService::class);

    $service->startProcessing($order);

    expect($order->fresh()->status)->toBe(Order::STATUS_IN_PROGRESS)
        ->and($customer->notifications()->count())->toBe(1);

    $service->complete($order);

    expect($order->fresh()->status)->toBe(Order::STATUS_COMPLETED)
        ->and($customer->notifications()->count())->toBe(2);

    Mail::assertSent(ApointNotificationMail::class, fn (ApointNotificationMail $mail): bool => (
        $mail->title === 'Your order is complete'
        && $mail->actionUrl === route('orders.show', $order)
    ));
});
