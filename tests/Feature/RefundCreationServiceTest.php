<?php

use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a pending refund for a successful payment', function () {
    $payment = Payment::factory()->create([
        'amount' => '10000.00',
        'status' => Payment::STATUS_SUCCESSFUL,
    ]);

    $refund = app(RefundCreationService::class)->create(
        $payment,
        '2500.00',
        'Customer requested a partial refund.'
    );

    expect($refund)
        ->toBeInstanceOf(Refund::class)
        ->and($refund->payment_id)->toBe($payment->id)
        ->and($refund->reference)->toBe('APT-R-2026-000001')
        ->and($refund->amount)->toBe('2500.00')
        ->and($refund->status)->toBe(Refund::STATUS_PENDING)
        ->and($refund->provider_reference)->toBeNull()
        ->and($refund->reason)->toBe('Customer requested a partial refund.')
        ->and($refund->initiated_at)->not->toBeNull()
        ->and($refund->completed_at)->toBeNull();

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_SUCCESSFUL);
});

it('rejects refunds against unsuccessful payments', function () {
    $payment = Payment::factory()->create([
        'amount' => '10000.00',
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(RefundCreationService::class)->create(
        $payment,
        '2500.00'
    ))->toThrow(InvalidArgumentException::class);
});

it('rejects zero and negative refund amounts', function () {
    $payment = Payment::factory()->create([
        'amount' => '10000.00',
        'status' => Payment::STATUS_SUCCESSFUL,
    ]);

    $service = app(RefundCreationService::class);

    expect(fn () => $service->create($payment, '0.00'))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $service->create($payment, '-100.00'))
        ->toThrow(InvalidArgumentException::class);
});

it('prevents cumulative active refunds from exceeding the payment amount', function () {
    $payment = Payment::factory()->create([
        'amount' => '10000.00',
        'status' => Payment::STATUS_SUCCESSFUL,
    ]);

  Refund::create([
    'payment_id' => $payment->id,
    'reference' => 'APT-R-2026-000100',
    'amount' => '7000.00',
    'status' => Refund::STATUS_PENDING,
]);

    expect(fn () => app(RefundCreationService::class)->create(
        $payment,
        '4000.00'
    ))->toThrow(InvalidArgumentException::class);
});

it('allows multiple partial refunds up to the payment amount', function () {
    $payment = Payment::factory()->create([
        'amount' => '10000.00',
        'status' => Payment::STATUS_SUCCESSFUL,
    ]);

    $service = app(RefundCreationService::class);

    $service->create($payment, '3000.00');
    $service->create($payment, '7000.00');

    expect($payment->refunds()->count())->toBe(2);

    expect(fn () => $service->create($payment, '0.01'))
        ->toThrow(InvalidArgumentException::class);
});

it('releases refundable balance when an existing refund fails', function () {
    $payment = Payment::factory()->create([
        'amount' => '10000.00',
        'status' => Payment::STATUS_SUCCESSFUL,
    ]);

    Refund::create([
        'payment_id' => $payment->id,
        'reference' => 'APT-R-2026-000100',
        'amount' => '7000.00',
        'status' => Refund::STATUS_FAILED,
    ]);

    $refund = app(RefundCreationService::class)->create(
        $payment,
        '7000.00'
    );

    expect($refund->amount)->toBe('7000.00')
        ->and($payment->refunds()->count())->toBe(2);
});