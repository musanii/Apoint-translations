<?php

use App\Models\Currency;
use App\Models\Service;
use App\Models\User;
use App\Services\QuoteReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create();

    $service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'is_active' => true,
    ]);

    $currency = Currency::create([
        'code' => 'KES',
        'name' => 'Kenyan Shilling',
        'symbol' => 'KSh',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->quoteRequest = $user->quoteRequests()->create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
    ]);

    $this->currency = $currency;

    $this->generator = app(QuoteReferenceGenerator::class);
});

test('it generates the first quote reference for the current year', function () {
    $reference = $this->generator->generate();

    expect($reference)->toBe(
        'APT-Q-'.now()->year.'-000001'
    );
});

test('it increments the reference number sequentially', function () {
    $firstReference = $this->generator->generate();
    $secondReference = $this->generator->generate();

    expect($firstReference)->toBe(
        'APT-Q-'.now()->year.'-000001'
    );

    expect($secondReference)->toBe(
        'APT-Q-'.now()->year.'-000002'
    );
});

test('it starts a new sequence for a new year', function () {
    $this->travelTo(now()->setDate(2026, 12, 31));

    $firstReference = $this->generator->generate();

    $this->travelTo(now()->setDate(2027, 1, 1));

    $secondReference = $this->generator->generate();

    expect($firstReference)->toBe('APT-Q-2026-000001')
        ->and($secondReference)->toBe('APT-Q-2027-000001');
});
