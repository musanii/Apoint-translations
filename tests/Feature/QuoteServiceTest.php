<?php

use App\Models\Currency;
use App\Models\Language;
use App\Models\PricingRule;
use App\Models\Service;
use App\Services\PricingCalculator;
use App\Services\QuoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it finds the matching pricing rule and calculates a quote', function () {
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

    $sourceLanguage = Language::create([
        'name' => 'English',
        'code' => 'en',
        'is_active' => true,
    ]);

    $targetLanguage = Language::create([
        'name' => 'Swahili',
        'code' => 'sw',
        'is_active' => true,
    ]);

    PricingRule::create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'source_language_id' => $sourceLanguage->id,
        'target_language_id' => $targetLanguage->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 2.50,
        'minimum_charge' => 500,
        'is_active' => true,
    ]);

    $french = Language::create([
        'name' => 'French',
        'code' => 'fr',
        'is_active' => true,
    ]);

    PricingRule::create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'source_language_id' => $sourceLanguage->id,
        'target_language_id' => $french->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 3.50,
        'minimum_charge' => 500,
        'is_active' => true,
    ]);
    expect(PricingRule::where('target_language_id', $targetLanguage->id)
        ->first()
        ->rate)->toBe('2.5000');

    expect(Service::count())->toBe(1);
    expect(Currency::count())->toBe(1);
    expect(Language::count())->toBe(3);
    expect(PricingRule::count())->toBe(2);

    $pricingRule = PricingRule::first();

    expect($pricingRule->service->name)
        ->toBe('Document Translation');

    expect($pricingRule->currency->code)
        ->toBe('KES');

    expect($pricingRule->sourceLanguage->code)
        ->toBe('en');

    expect($pricingRule->targetLanguage->code)
        ->toBe('sw');

    $quoteService = new QuoteService(
        new PricingCalculator
    );

    $quote = $quoteService->calculate(
        serviceId: $service->id,
        sourceLanguageId: $sourceLanguage->id,
        targetLanguageId: $targetLanguage->id,
        currencyId: $currency->id,
        quantity: 200
    );

    expect($quote['pricing_type'])->toBe('per_word');
    expect($quote['unit'])->toBe('word');
    expect($quote['quantity'])->toBe(200);
    expect($quote['rate'])->toBe(2.5);
    expect($quote['total'])->toBe(500.0);
    expect($quote['currency'])->toBe('KES');
});

test('it rejects requests without a matching pricing rule', function () {
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

    $english = Language::create([
        'name' => 'English',
        'code' => 'en',
        'is_active' => true,
    ]);

    $german = Language::create([
        'name' => 'German',
        'code' => 'de',
        'is_active' => true,
    ]);

    $quoteService = new QuoteService(
        new PricingCalculator
    );

    expect(fn () => $quoteService->calculate(
        serviceId: $service->id,
        sourceLanguageId: $english->id,
        targetLanguageId: $german->id,
        currencyId: $currency->id,
        quantity: 200
    ))->toThrow(
        ModelNotFoundException::class
    );
});

test('it rejects inactive source or target languages', function () {
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

    $english = Language::create([
        'name' => 'English',
        'code' => 'en',
        'is_active' => true,
    ]);

    $swahili = Language::create([
        'name' => 'Swahili',
        'code' => 'sw',
        'is_active' => false,
    ]);

    PricingRule::create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'source_language_id' => $english->id,
        'target_language_id' => $swahili->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 2.50,
        'minimum_charge' => 500,
        'is_active' => true,
    ]);

    $quoteService = new QuoteService(
        new PricingCalculator
    );

    expect(fn () => $quoteService->calculate(
        serviceId: $service->id,
        sourceLanguageId: $english->id,
        targetLanguageId: $swahili->id,
        currencyId: $currency->id,
        quantity: 200
    ))->toThrow(
        ModelNotFoundException::class
    );
});

test('it rejects inactive services', function () {
    $service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'is_active' => false,
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

    PricingRule::create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'source_language_id' => $english->id,
        'target_language_id' => $swahili->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 2.50,
        'minimum_charge' => 500,
        'is_active' => true,
    ]);

    $quoteService = new QuoteService(
        new PricingCalculator
    );

    expect(fn () => $quoteService->calculate(
        serviceId: $service->id,
        sourceLanguageId: $english->id,
        targetLanguageId: $swahili->id,
        currencyId: $currency->id,
        quantity: 200
    ))->toThrow(
        ModelNotFoundException::class
    );
});

test('it rejects inactive currencies', function () {
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
        'is_active' => false,
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

    PricingRule::create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'source_language_id' => $english->id,
        'target_language_id' => $swahili->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 2.50,
        'minimum_charge' => 500,
        'is_active' => true,
    ]);

    $quoteService = new QuoteService(
        new PricingCalculator
    );

    expect(fn () => $quoteService->calculate(
        serviceId: $service->id,
        sourceLanguageId: $english->id,
        targetLanguageId: $swahili->id,
        currencyId: $currency->id,
        quantity: 200
    ))->toThrow(
        ModelNotFoundException::class
    );
});

test('it rejects inactive pricing rules', function () {
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

    PricingRule::create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'source_language_id' => $english->id,
        'target_language_id' => $swahili->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 2.50,
        'minimum_charge' => 500,
        'is_active' => false,
    ]);

    $quoteService = new QuoteService(
        new PricingCalculator
    );

    expect(fn () => $quoteService->calculate(
        serviceId: $service->id,
        sourceLanguageId: $english->id,
        targetLanguageId: $swahili->id,
        currencyId: $currency->id,
        quantity: 200
    ))->toThrow(
        ModelNotFoundException::class
    );
});
