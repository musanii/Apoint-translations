<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCreationService;

it('creates a pending payment using the order total and currency', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
        'total' => 1250.00,
    ]);

    $payment = app(PaymentCreationService::class)->createFor(
        $order,
        'mpesa'
    );

    expect($payment)
        ->toBeInstanceOf(Payment::class)
        ->and($payment->order_id)->toBe($order->id)
        ->and($payment->currency_id)->toBe($order->currency_id)
        ->and((float) $payment->amount)->toBe(1250.00)
        ->and($payment->provider)->toBe('mpesa')
        ->and($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->reference)->not->toBeEmpty()
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PENDING);
});

it('does not create another payment while an attempt is pending', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PaymentCreationService::class)->createFor(
        $order,
        'mpesa'
    ))->toThrow(InvalidArgumentException::class);

    expect($order->payments()->count())->toBe(1);
});

it('allows a new payment attempt after a previous attempt failed', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_FAILED,
    ]);

    $payment = app(PaymentCreationService::class)->createFor(
        $order,
        'mpesa'
    );

    expect($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($order->payments()->count())->toBe(2)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PENDING);
});

it('does not create a payment for a cancelled order', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_CANCELLED,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);

    expect(fn () => app(PaymentCreationService::class)->createFor(
        $order,
        'mpesa'
    ))->toThrow(InvalidArgumentException::class);

    expect($order->payments()->count())->toBe(0);
});

it('does not create a payment for an already paid order', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    expect(fn () => app(PaymentCreationService::class)->createFor(
        $order,
        'mpesa'
    ))->toThrow(InvalidArgumentException::class);

    expect($order->payments()->count())->toBe(0);
});

it('rejects an unsupported payment provider', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);

    expect(fn () => app(PaymentCreationService::class)->createFor(
        $order,
        'unsupported-provider'
    ))->toThrow(InvalidArgumentException::class);

    expect($order->payments()->count())->toBe(0);
    expect($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_UNPAID);
});