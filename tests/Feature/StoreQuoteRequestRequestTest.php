<?php

use App\Http\Requests\StoreQuoteRequestRequest;
use App\Models\Currency;
use App\Models\Service;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'description' => 'Document translation services.',
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

    $this->baseRequestData = [
        'service_id' => $service->id,
        'source_language_id' => null,
        'target_language_id' => null,
        'currency_id' => $currency->id,
    ];
});

test('digital delivery does not require physical delivery details', function () {
    $request = new StoreQuoteRequestRequest();

    $validator = Validator::make([
        ...$this->baseRequestData,
        'delivery_method' => 'digital',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('physical delivery requires recipient and destination details', function () {
    $request = new StoreQuoteRequestRequest();

    $validator = Validator::make([
        ...$this->baseRequestData,
        'delivery_method' => 'physical',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain(
            'recipient_name',
            'recipient_phone',
            'delivery_country',
            'delivery_address',
        );
});

test('it rejects unsupported delivery methods', function () {
    $request = new StoreQuoteRequestRequest();

    $validator = Validator::make([
        ...$this->baseRequestData,
        'delivery_method' => 'drone',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('delivery_method'))->toBeTrue();
});

test('physical delivery accepts complete recipient details', function () {
    $request = new StoreQuoteRequestRequest();

    $validator = Validator::make([
        ...$this->baseRequestData,
        'delivery_method' => 'physical',
        'recipient_name' => 'Jane Doe',
        'recipient_phone' => '+254712345678',
        'delivery_country' => 'Kenya',
        'delivery_address' => 'Westlands, Nairobi',
        'delivery_postal_code' => '00800',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});
