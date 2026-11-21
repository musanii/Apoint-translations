<?php

use App\Models\Quote;
use App\Services\QuoteStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

uses(RefreshDatabase::class);

test('a draft quote can be sent', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_DRAFT,
    ]);

    $service = app(QuoteStatusService::class);

    $sentQuote = $service->send($quote);

    expect($sentQuote->status)->toBe(Quote::STATUS_SENT)
        ->and($sentQuote->sent_at)->not->toBeNull();
});

test('a sent quote cannot be sent again', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
    ]);

    $service = app(QuoteStatusService::class);

    expect(fn () => $service->send($quote))
        ->toThrow(InvalidArgumentException::class);
});

test('a sent quote can be accepted', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
    ]);

    $service = app(QuoteStatusService::class);

    $acceptedQuote = $service->accept($quote);

    expect($acceptedQuote->status)->toBe(Quote::STATUS_ACCEPTED)
        ->and($acceptedQuote->accepted_at)->not->toBeNull();
});

test('a sent quote can be rejected', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
    ]);

    $service = app(QuoteStatusService::class);

    $rejectedQuote = $service->reject($quote);

    expect($rejectedQuote->status)->toBe(Quote::STATUS_REJECTED)
        ->and($rejectedQuote->rejected_at)->not->toBeNull();
});

test('a sent quote can expire after its validity date', function () {
    Carbon::setTestNow('2026-11-09 12:00:00');

    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
        'valid_until' => now()->subDay(),
    ]);

    $service = app(QuoteStatusService::class);

    $expiredQuote = $service->expire($quote);

    expect($expiredQuote->status)->toBe(Quote::STATUS_EXPIRED);
});

test('a sent quote cannot expire before its validity date', function () {
    Carbon::setTestNow('2026-11-09 12:00:00');

    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
        'valid_until' => now()->addDay(),
    ]);

    $service = app(QuoteStatusService::class);

    expect(fn () => $service->expire($quote))
        ->toThrow(InvalidArgumentException::class);
});

test('an accepted quote cannot be rejected', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_ACCEPTED,
    ]);

    $service = app(QuoteStatusService::class);

    expect(fn () => $service->reject($quote))
        ->toThrow(InvalidArgumentException::class);
});

test('a rejected quote cannot be accepted', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_REJECTED,
    ]);

    $service = app(QuoteStatusService::class);

    expect(fn () => $service->accept($quote))
        ->toThrow(InvalidArgumentException::class);
});

test('an expired quote cannot be accepted', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_EXPIRED,
    ]);

    $service = app(QuoteStatusService::class);

    expect(fn () => $service->accept($quote))
        ->toThrow(InvalidArgumentException::class);
});
