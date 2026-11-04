<?php

use App\Models\Currency;
use App\Models\Language;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can create and retrieve a quote request', function () {
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

    $english = Language::create([
        'name' => 'English',
        'code' => 'en',
        'is_active' => true,
    ]);

    $swahili = Language::create([
        'name' => 'Swahili',
        'code' => 'sw',
        'is_active' => true,
    ]);

    $quoteRequest = $user->quoteRequests()->create([
        'service_id' => $service->id,
        'source_language_id' => $english->id,
        'target_language_id' => $swahili->id,
        'currency_id' => $currency->id,
        'quantity' => 500,
        'unit' => 'word',
        'delivery_date' => '2026-11-10',
        'instructions' => 'Please translate this document.',
    ]);

    expect($quoteRequest->user->id)->toBe($user->id);
    expect($quoteRequest->service->name)->toBe('Document Translation');
    expect($quoteRequest->sourceLanguage->code)->toBe('en');
    expect($quoteRequest->targetLanguage->code)->toBe('sw');
    expect($quoteRequest->currency->code)->toBe('KES');
    expect($quoteRequest->status)->toBe('pending');

    expect($user->quoteRequests)->toHaveCount(1);
});

test('a quote request can be created without language fields', function () {
    $user = User::factory()->create();

    $service = Service::create([
        'name' => 'Proofreading',
        'slug' => 'proofreading',
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

    $quoteRequest = $user->quoteRequests()->create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'instructions' => 'Please proofread my document.',
    ]);

    expect($quoteRequest->source_language_id)->toBeNull();
    expect($quoteRequest->target_language_id)->toBeNull();
    expect($quoteRequest->status)->toBe('pending');
});

test('a quote request cannot reference a nonexistent service', function () {
    $user = User::factory()->create();

    $currency = Currency::create([
        'code' => 'KES',
        'name' => 'Kenyan Shilling',
        'symbol' => 'KSh',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => true,
    ]);

    expect(fn () => $user->quoteRequests()->create([
        'service_id' => 99999,
        'currency_id' => $currency->id,
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});