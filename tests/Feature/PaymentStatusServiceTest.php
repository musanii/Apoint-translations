<?php

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
        ->not->toBeNull()
        ->and($order->status)
        ->toBe(Order::STATUS_CONFIRMED);
});

it('does not mark a payment as failed when it was already marked successful', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    // Keep this model instance stale, as reconciliation might.
    $stalePayment = $payment;

    $service = app(PaymentStatusService::class);

    // A callback successfully processes the payment first.
    $service->markSuccessful($payment, 'MPESA-TRANSACTION-123');

    // Reconciliation subsequently tries to mark the stale payment failed.
    expect(fn () => $service->markFailed(
        $stalePayment,
        'Payment was declined.'
    ))->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->fresh()->provider_reference)
        ->toBe('MPESA-TRANSACTION-123')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID);
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

it('attaches a provider reference to an already successful payment', function () {
    $paidAt = now()->subMinutes(5);

    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'paid_at' => $paidAt,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => null,
        'paid_at' => $paidAt,
    ]);

    $result = app(PaymentStatusService::class)
        ->attachProviderReference($payment, 'TESTRCPT002');

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBe('TESTRCPT002')
        ->and($result->paid_at->toDateTimeString())
        ->toBe($paidAt->toDateTimeString())
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($order->fresh()->paid_at->toDateTimeString())
        ->toBe($paidAt->toDateTimeString());
});

it('accepts the same provider reference when attached more than once', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => 'TESTRCPT002',
    ]);

    $service = app(PaymentStatusService::class);

    $firstResult = $service->attachProviderReference(
        $payment,
        'TESTRCPT002'
    );

    $secondResult = $service->attachProviderReference(
        $payment,
        'TESTRCPT002'
    );

    expect($firstResult->provider_reference)
        ->toBe('TESTRCPT002')
        ->and($secondResult->provider_reference)
        ->toBe('TESTRCPT002')
        ->and($secondResult->status)
        ->toBe(Payment::STATUS_SUCCESSFUL);
});

it('does not attach a provider reference to a failed payment', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_reference' => null,
    ]);

    expect(fn () => app(PaymentStatusService::class)
        ->attachProviderReference($payment, 'TESTRCPT003'))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->provider_reference)->toBeNull();
});

it('does not overwrite an existing provider reference', function () {
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => 'TESTRCPT002',
    ]);

    expect(fn () => app(PaymentStatusService::class)
        ->attachProviderReference($payment, 'TESTRCPT003'))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->provider_reference)
        ->toBe('TESTRCPT002');
});

it('rejects a provider reference already attached to another payment', function () {
    $firstOrder = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    Payment::factory()->create([
        'order_id' => $firstOrder->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => 'TESTRCPT004',
    ]);

    $secondOrder = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $secondPayment = Payment::factory()->create([
        'order_id' => $secondOrder->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => null,
    ]);

    expect(fn () => app(PaymentStatusService::class)
        ->attachProviderReference($secondPayment, 'TESTRCPT004'))
        ->toThrow(InvalidArgumentException::class);

    expect($secondPayment->fresh()->provider_reference)->toBeNull();
});

it('recovers a failed payment after verified M-Pesa success', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
        'paid_at' => null,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_reference' => null,
        'failure_reason' => 'Payment timed out.',
        'paid_at' => null,
    ]);

    $result = app(PaymentStatusService::class)
        ->recoverFailedPayment($payment, 'MPESA-LATE-SUCCESS-123');

    $payment->refresh();
    $order->refresh();

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->provider_reference)
        ->toBe('MPESA-LATE-SUCCESS-123')
        ->and($payment->paid_at)
        ->not->toBeNull()
        ->and($payment->failure_reason)
        ->toBeNull()
        ->and($order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($order->paid_at)
        ->not->toBeNull()
        ->and($order->status)
        ->toBe(Order::STATUS_CONFIRMED);
});

it('does not recover a failed payment when its order is no longer awaiting payment', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_reference' => null,
    ]);

    expect(fn () => app(PaymentStatusService::class)
        ->recoverFailedPayment($payment, 'MPESA-LATE-SUCCESS-456'))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($payment->fresh()->provider_reference)
        ->toBeNull()
        ->and($order->fresh()->status)
        ->toBe(Order::STATUS_CONFIRMED);
});

it('does not recover a failed payment when another payment attempt has already succeeded', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
    ]);

    $failedPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_reference' => null,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => 'MPESA-ALREADY-PAID-123',
    ]);

    expect(fn () => app(PaymentStatusService::class)
        ->recoverFailedPayment($failedPayment, 'MPESA-LATE-SUCCESS-789'))
        ->toThrow(InvalidArgumentException::class);

    expect($failedPayment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($failedPayment->fresh()->provider_reference)
        ->toBeNull();
});

it('does not recover a failed payment with an empty provider reference', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_reference' => null,
    ]);

    expect(fn () => app(PaymentStatusService::class)
        ->recoverFailedPayment($payment, '   '))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($payment->fresh()->provider_reference)
        ->toBeNull()
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('does not recover a failed payment with a provider reference already used elsewhere', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
    ]);

    $failedPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_reference' => null,
    ]);

    Payment::factory()->create([
        'provider' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => 'MPESA-DUPLICATE-123',
    ]);

    expect(fn () => app(PaymentStatusService::class)
        ->recoverFailedPayment($failedPayment, 'MPESA-DUPLICATE-123'))
        ->toThrow(InvalidArgumentException::class);

    expect($failedPayment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($failedPayment->fresh()->provider_reference)
        ->toBeNull();
});

it('records another successful payment without confirming an already paid order twice', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'total' => 100,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_reference' => 'FIRSTRECEIPT123',
    ]);

    $secondPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
        'provider_reference' => null,
    ]);

    $result = app(PaymentStatusService::class)->markSuccessful(
        $secondPayment,
        'SECONDRECEIPT123'
    );

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBe('SECONDRECEIPT123')
        ->and($order->fresh()->status)
        ->toBe(Order::STATUS_CONFIRMED)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID);
});

it('recovers a failed payment while another payment attempt is pending', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
        'total' => 100,
    ]);

    $failedPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_FAILED,
        'failure_reason' => 'Payment timed out.',
        'provider_reference' => null,
    ]);

    $pendingPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
        'provider_reference' => null,
    ]);

    $result = app(PaymentStatusService::class)->recoverFailedPayment(
        $failedPayment,
        'LATESUCCESS123'
    );

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBe('LATESUCCESS123')
        ->and($pendingPayment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($order->fresh()->status)
        ->toBe(Order::STATUS_CONFIRMED);
});
