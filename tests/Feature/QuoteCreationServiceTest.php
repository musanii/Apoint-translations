<?php

use App\Models\Currency;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use App\Services\QuoteCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'description' => 'Translation of official documents',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->currency = Currency::create([
        'code' => 'KES',
        'name' => 'Kenyan Shilling',
        'symbol' => 'KSh',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->quoteRequest = QuoteRequest::create([
        'user_id' => $this->user->id,
        'service_id' => $this->service->id,
        'currency_id' => $this->currency->id,
        'status' => 'pending',
    ]);

    $this->quoteCreationService = app(QuoteCreationService::class);
});

test('it creates a draft quote for a quote request', function () {
    $quote = $this->quoteCreationService->create(
        quoteRequest: $this->quoteRequest,
        pricingType: 'per_word',
        quantity: 100,
        unit: 'word',
        rate: 2.50,
        subtotal: 250.00,
    );

    expect($quote)
        ->toBeInstanceOf(Quote::class)
        ->and($quote->quote_request_id)->toBe($this->quoteRequest->id)
        ->and($quote->currency_id)->toBe($this->currency->id)
        ->and($quote->pricing_type)->toBe('per_word')
        ->and($quote->total)->toBe('250.00')
        ->and($quote->status)->toBe(Quote::STATUS_DRAFT)
        ->and($quote->reference)->toStartWith('APT-Q-');
});


test('it includes express and certification fees in the total', function () {
    $quote = $this->quoteCreationService->create(
        quoteRequest: $this->quoteRequest,
        pricingType: 'per_page',
        quantity: 5,
        unit: 'page',
        rate: 100,
        subtotal: 500,
        expressFee: 150,
        certificationFee: 200,
        courierFee: 100,
    );

  expect($quote->subtotal)->toBe('500.00')
    ->and($quote->express_fee)->toBe('150.00')
    ->and($quote->certification_fee)->toBe('200.00')
    ->and($quote->courier_fee)->toBe('100.00')
    ->and($quote->total)->toBe('950.00');
});

test('it defaults optional fees to zero', function () {
    $quote = $this->quoteCreationService->create(
        quoteRequest: $this->quoteRequest,
        pricingType: 'fixed',
        quantity: null,
        unit: null,
        rate: null,
        subtotal: 750,
    );

    expect($quote->express_fee)->toBe('0.00')
        ->and($quote->certification_fee)->toBe('0.00')
        ->and($quote->courier_fee)->toBe('0.00')
        ->and($quote->total)->toBe('750.00');
});