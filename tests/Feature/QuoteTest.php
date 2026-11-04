<?php

use App\Models\Currency;
use App\Models\Quote;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'is_active' => true,
    ]);

    $this->currency = Currency::create([
        'code' => 'KES',
        'name' => 'Kenyan Shilling',
        'symbol' => 'KSh',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->quoteRequest = $this->user->quoteRequests()->create([
        'service_id' => $service->id,
        'currency_id' => $this->currency->id,
        'quantity' => 100,
        'unit' => 'word',
    ]);
});

test('a quote belongs to a quote request', function () {
    $quote = Quote::create([
        'quote_request_id' => $this->quoteRequest->id,
        'currency_id' => $this->currency->id,
        'reference' => 'APT-Q-2026-0001',
        'pricing_type' => 'per_word',
        'quantity' => 100,
        'unit' => 'word',
        'rate' => 2.50,
        'subtotal' => 250,
        'total' => 250,
    ]);

    expect($quote->quoteRequest->id)->toBe($this->quoteRequest->id);
});

test('a quote belongs to a currency', function () {
    $quote = Quote::create([
        'quote_request_id' => $this->quoteRequest->id,
        'currency_id' => $this->currency->id,
        'reference' => 'APT-Q-2026-0002',
        'pricing_type' => 'per_word',
        'subtotal' => 250,
        'total' => 250,
    ]);

    expect($quote->currency->code)->toBe('KES');
});

test('a quote request can have multiple quotes', function () {
    foreach (['APT-Q-2026-0003', 'APT-Q-2026-0004'] as $reference) {
        Quote::create([
            'quote_request_id' => $this->quoteRequest->id,
            'currency_id' => $this->currency->id,
            'reference' => $reference,
            'pricing_type' => 'custom',
            'subtotal' => 300,
            'total' => 300,
        ]);
    }

    expect($this->quoteRequest->quotes()->count())->toBe(2);
});

test('a new quote defaults to draft status', function () {
    $quote = Quote::create([
        'quote_request_id' => $this->quoteRequest->id,
        'currency_id' => $this->currency->id,
        'reference' => 'APT-Q-2026-0005',
        'pricing_type' => 'custom',
        'subtotal' => 300,
        'total' => 300,
    ]);

    expect($quote->fresh()->status)->toBe(Quote::STATUS_DRAFT);
});
