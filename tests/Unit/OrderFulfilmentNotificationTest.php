<?php

use App\Mail\ApointNotificationMail;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Artisan::call('migrate:fresh', ['--force' => true]);
});

it('notifies the customer when fulfilment starts and emails them when it completes', function () {
    Mail::fake();

    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'delivery_method' => 'physical',
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
