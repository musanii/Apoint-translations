<?php

use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('marks a pending payment as successful and updates its order', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
        'provider_reference' => null,
        'paid_at' => null,
    ]);

    $service = app(PaymentStatusService::class);

    $service->markSuccessful($payment, 'MPESA-TRANSACTION-123');

    $payment->refresh();
    $order->refresh();

    expect($payment->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->provider_reference)
        ->toBe('MPESA-TRANSACTION-123')
        ->and($payment->paid_at)
        ->not->toBeNull()
        ->and($order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($order->paid_at)
        ->not->toBeNull();
});

it('does not mark a failed payment as successful', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_FAILED,
    ]);

    $service = app(PaymentStatusService::class);

    expect(fn () => $service->markSuccessful(
        $payment,
        'MPESA-TRANSACTION-123'
    ))->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('marks a pending payment as failed and updates its order', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    $service = app(PaymentStatusService::class);

    $service->markFailed($payment, 'Payment was declined.');

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($payment->fresh()->failure_reason)
        ->toBe('Payment was declined.')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('keeps the order payment pending when another attempt is still pending', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $failedAttempt = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    app(PaymentStatusService::class)->markFailed(
        $failedAttempt,
        'Payment was declined.'
    );

    expect($failedAttempt->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PENDING);
});

it('does not overwrite a paid order when another attempt fails', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'paid_at' => now(),
    ]);

    $failedAttempt = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_SUCCESSFUL,
    ]);

    app(PaymentStatusService::class)->markFailed(
        $failedAttempt,
        'Payment was declined.'
    );

    expect($failedAttempt->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID);
});

it('does not process a successful payment twice', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    $service = app(PaymentStatusService::class);

    $service->markSuccessful($payment, 'MPESA-TRANSACTION-123');

    expect(fn () => $service->markSuccessful(
        $payment,
        'MPESA-TRANSACTION-456'
    ))->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->provider_reference)
        ->toBe('MPESA-TRANSACTION-123');
});

it('rejects a provider transaction reference already used by another payment', function () {
    $firstOrder = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $firstPayment = Payment::factory()->create([
        'order_id' => $firstOrder->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
    ]);

    $secondOrder = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $secondPayment = Payment::factory()->create([
        'order_id' => $secondOrder->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
    ]);

    $service = app(PaymentStatusService::class);

    $service->markSuccessful($firstPayment, 'MPESA-DUPLICATE-123');

    expect(fn () => $service->markSuccessful(
        $secondPayment,
        'MPESA-DUPLICATE-123'
    ))->toThrow(InvalidArgumentException::class);

    expect($secondPayment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($secondOrder->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PENDING);
});