
<?php

use App\Models\Payment;
use App\Models\Refund;
use App\Services\RefundStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks a pending refund as processing', function () {
    $refund = Refund::factory()->create([
        'status' => Refund::STATUS_PENDING,
    ]);

    $updated = app(RefundStatusService::class)->markProcessing($refund);

    expect($updated->status)->toBe(Refund::STATUS_PROCESSING)
        ->and($updated->completed_at)->toBeNull();
});

it('marks a pending refund as successful', function () {
    $refund = Refund::factory()->create([
        'status' => Refund::STATUS_PENDING,
    ]);

    $updated = app(RefundStatusService::class)->markSuccessful(
        $refund,
        'PROVIDER-REFUND-123'
    );

    expect($updated->status)->toBe(Refund::STATUS_SUCCESSFUL)
        ->and($updated->provider_reference)->toBe('PROVIDER-REFUND-123')
        ->and($updated->completed_at)->not->toBeNull();
});

it('marks a processing refund as failed', function () {
    $refund = Refund::factory()->create([
        'status' => Refund::STATUS_PROCESSING,
    ]);

    $updated = app(RefundStatusService::class)->markFailed(
        $refund,
        'Provider rejected the refund.'
    );

    expect($updated->status)->toBe(Refund::STATUS_FAILED)
        ->and($updated->failure_reason)->toBe('Provider rejected the refund.')
        ->and($updated->completed_at)->not->toBeNull();
});

it('does not allow a completed refund to change status', function () {
    $refund = Refund::factory()->create([
        'status' => Refund::STATUS_SUCCESSFUL,
        'completed_at' => now(),
    ]);

    expect(fn () => app(RefundStatusService::class)->markFailed(
        $refund,
        'Late failure notification.'
    ))->toThrow(InvalidArgumentException::class);
});
