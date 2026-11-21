<?php

use App\Models\Currency;
use App\Models\Language;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\QueryException;
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
    ]))->toThrow(QueryException::class);
});

test('a quote request can store physical delivery details', function () {
    $user = User::factory()->create();

    $service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $currency = Currency::create([
        'code' => 'KES',
        'name' => 'Kenyan Shilling',
        'symbol' => 'KSh',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => true,
    ]);

    $quoteRequest = QuoteRequest::create([
        'user_id' => $user->id,
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'delivery_method' => 'physical',
        'recipient_name' => 'Jane Doe',
        'recipient_phone' => '+254712345678',
        'delivery_country' => 'Kenya',
        'delivery_address' => 'Westlands, Nairobi',
        'delivery_postal_code' => '00800',
    ]);

    expect($quoteRequest->delivery_method)->toBe('physical')
        ->and($quoteRequest->recipient_name)->toBe('Jane Doe')
        ->and($quoteRequest->recipient_phone)->toBe('+254712345678')
        ->and($quoteRequest->delivery_country)->toBe('Kenya')
        ->and($quoteRequest->delivery_address)->toBe('Westlands, Nairobi')
        ->and($quoteRequest->delivery_postal_code)->toBe('00800');
});
