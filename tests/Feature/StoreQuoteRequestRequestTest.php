<?php

use App\Http\Requests\StoreQuoteRequestRequest;
use Illuminate\Support\Facades\Validator;

test('digital delivery does not require physical delivery details', function () {
    $request = new StoreQuoteRequestRequest();

    $validator = Validator::make([
        'delivery_method' => 'digital',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('physical delivery requires recipient and destination details', function () {
    $request = new StoreQuoteRequestRequest();

    $validator = Validator::make([
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
        'delivery_method' => 'drone',
    ], $request->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('delivery_method'))->toBeTrue();
});

test('physical delivery accepts complete recipient details', function () {
    $request = new StoreQuoteRequestRequest();

    $validator = Validator::make([
        'delivery_method' => 'physical',
        'recipient_name' => 'Jane Doe',
        'recipient_phone' => '+254712345678',
        'delivery_country' => 'Kenya',
        'delivery_address' => 'Westlands, Nairobi',
        'delivery_postal_code' => '00800',
    ], $request->rules());

    expect($validator->passes())->toBeTrue();
});