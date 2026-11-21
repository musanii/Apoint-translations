<?php

use App\Models\Order;
use App\Models\Payment;

it('identifies only eligible pending M-Pesa payments for reconciliation', function () {
    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $eligiblePayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-eligible',
        'reconciliation_attempts' => 0,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $notYetDuePayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-not-due',
        'reconciliation_attempts' => 0,
        'next_reconciliation_at' => now()->addMinutes(5),
    ]);

    $successfulPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_checkout_request_id' => 'checkout-successful',
        'reconciliation_attempts' => 0,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $failedPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_checkout_request_id' => 'checkout-failed',
        'reconciliation_attempts' => 0,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $otherProviderPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'other',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-other',
        'reconciliation_attempts' => 0,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $exhaustedPayment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-exhausted',
        'reconciliation_attempts' => 6,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    expect(Payment::query()
        ->where('provider', 'daraja')
        ->where('payment_method', 'mpesa')
        ->where('status', Payment::STATUS_PENDING)
        ->whereNotNull('provider_checkout_request_id')
        ->where('reconciliation_attempts', '<', 6)
        ->where(function ($query) {
            $query->whereNull('next_reconciliation_at')
                ->orWhere('next_reconciliation_at', '<=', now());
        })
        ->pluck('id')
        ->all())
        ->toBe([$eligiblePayment->id]);
});

use App\Services\MpesaPaymentReconciliationRunner;
use App\Services\MpesaPaymentReconciliationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery;

it('claims a due payment and sends it for reconciliation', function () {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-runner-test',
        'reconciliation_attempts' => 0,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $reconciliationService = Mockery::mock(
        MpesaPaymentReconciliationService::class
    );

    $reconciliationService
        ->shouldReceive('reconcile')
        ->once()
        ->with(Mockery::on(function (Payment $claimedPayment) use ($payment) {
            return $claimedPayment->id === $payment->id
                && $claimedPayment->reconciliation_attempts === 1;
        }))
        ->andReturn($payment);

    app()->instance(
        MpesaPaymentReconciliationService::class,
        $reconciliationService
    );

    $processed = app(MpesaPaymentReconciliationRunner::class)->run();

    $payment->refresh();

    expect($processed)->toBe(1);
    expect($payment->reconciliation_attempts)->toBe(1);
    expect($payment->last_reconciled_at)->not->toBeNull();
    expect($payment->next_reconciliation_at->format('Y-m-d H:i'))
        ->toBe($now->copy()->addMinutes(2)->format('Y-m-d H:i'));
    Carbon::setTestNow();
});

it('keeps a payment pending and schedules a retry when reconciliation throws an exception', function () {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-network-error',
        'reconciliation_attempts' => 0,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $reconciliationService = Mockery::mock(
        MpesaPaymentReconciliationService::class
    );

    $reconciliationService
        ->shouldReceive('reconcile')
        ->once()
        ->andThrow(new RuntimeException('Daraja is unavailable.'));

    app()->instance(
        MpesaPaymentReconciliationService::class,
        $reconciliationService
    );

    Log::shouldReceive('error')->once();

    app(MpesaPaymentReconciliationRunner::class)->run();

    $payment->refresh();

    expect($payment->status)->toBe(Payment::STATUS_PENDING);
    expect($payment->reconciliation_attempts)->toBe(1);
    expect($payment->last_reconciled_at)->not->toBeNull();
    expect($payment->next_reconciliation_at->format('Y-m-d H:i'))
        ->toBe($now->copy()->addMinutes(2)->format('Y-m-d H:i'));

    Carbon::setTestNow();
});

it('stops scheduling retries after the sixth attempt', function () {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-final-attempt',
        'reconciliation_attempts' => 5,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $reconciliationService = Mockery::mock(
        MpesaPaymentReconciliationService::class
    );

    $reconciliationService
        ->shouldReceive('reconcile')
        ->once()
        ->andReturn($payment);

    app()->instance(
        MpesaPaymentReconciliationService::class,
        $reconciliationService
    );

    $runner = app(MpesaPaymentReconciliationRunner::class);

    expect($runner->run())->toBe(1);

    $payment->refresh();

    expect($payment->reconciliation_attempts)->toBe(6);
    expect($payment->next_reconciliation_at)->toBeNull();

    // A second run must not query this exhausted payment again.
    expect($runner->run())->toBe(0);

    Carbon::setTestNow();
});
it('uses the correct retry delay after each reconciliation attempt', function (
    int $previousAttempts,
    int $expectedDelay
) {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'checkout-retry-'.$previousAttempts,
        'reconciliation_attempts' => $previousAttempts,
        'next_reconciliation_at' => now()->subMinute(),
    ]);

    $reconciliationService = Mockery::mock(
        MpesaPaymentReconciliationService::class
    );

    $reconciliationService
        ->shouldReceive('reconcile')
        ->once()
        ->andReturn($payment);

    app()->instance(
        MpesaPaymentReconciliationService::class,
        $reconciliationService
    );

    app(MpesaPaymentReconciliationRunner::class)->run();

    $payment->refresh();

    expect($payment->reconciliation_attempts)
        ->toBe($previousAttempts + 1);

    expect($payment->next_reconciliation_at->format('Y-m-d H:i'))
        ->toBe($now->copy()->addMinutes($expectedDelay)->format('Y-m-d H:i'));

    Carbon::setTestNow();
})->with([
    'after attempt 1' => [0, 2],
    'after attempt 2' => [1, 5],
    'after attempt 3' => [2, 15],
    'after attempt 4' => [3, 30],
    'after attempt 5' => [4, 60],
]);

it('does not process more payments than the supplied batch limit', function () {
    $now = Carbon::now();
    Carbon::setTestNow($now);

    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payments = collect(range(1, 3))->map(
        fn (int $number) => Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'daraja',
            'payment_method' => 'mpesa',
            'status' => Payment::STATUS_PENDING,
            'provider_checkout_request_id' => 'checkout-batch-'.$number,
            'reconciliation_attempts' => 0,
            'next_reconciliation_at' => now()->subMinute(),
        ])
    );

    $reconciliationService = Mockery::mock(
        MpesaPaymentReconciliationService::class
    );

    $reconciliationService
        ->shouldReceive('reconcile')
        ->twice()
        ->andReturnUsing(fn (Payment $payment) => $payment);

    app()->instance(
        MpesaPaymentReconciliationService::class,
        $reconciliationService
    );

    $processed = app(MpesaPaymentReconciliationRunner::class)->run(2);

    expect($processed)->toBe(2);

    $attemptedCount = Payment::query()
        ->whereIn('id', $payments->pluck('id'))
        ->where('reconciliation_attempts', 1)
        ->count();

    expect($attemptedCount)->toBe(2);

    Carbon::setTestNow();
});

it('rejects a batch limit less than one', function (int $limit) {
    $runner = app(MpesaPaymentReconciliationRunner::class);

    expect(fn () => $runner->run($limit))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'zero' => [0],
    'negative' => [-1],
]);

it('returns no payment when the selected payment no longer exists', function () {
    $runner = app(MpesaPaymentReconciliationRunner::class);

    $method = new ReflectionMethod(
        $runner,
        'claimAttempt'
    );

    $payment = $method->invoke($runner, 999999);

    expect($payment)->toBeNull();
});
