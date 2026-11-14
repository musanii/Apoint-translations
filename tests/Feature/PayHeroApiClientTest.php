<?php

use App\Services\PayHeroApiClient;

it('can be resolved from the service container', function () {
    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
    ]);

    expect(app(PayHeroApiClient::class))
        ->toBeInstanceOf(PayHeroApiClient::class);
});

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('sends an authenticated STK push request to PayHero', function () {
    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
    ]);

    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'success' => true,
            'status' => 'QUEUED',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
        ], 201),
    ]);

    $payload = [
        'amount' => 100,
        'phone_number' => '254712345678',
        'channel_id' => 12345,
        'provider' => 'm-pesa',
        'external_reference' => 'APT-PAY-123',
        'callback_url' => 'https://example.com/payhero/callback',
    ];

    $response = app(PayHeroApiClient::class)
        ->initiateStkPush($payload);

    Http::assertSent(function (Request $request) use ($payload) {
        return $request->url() === 'https://backend.payhero.co.ke/api/v2/payments'
            && $request->method() === 'POST'
            && $request->data() === $payload
            && $request->hasHeader(
                'Authorization',
                'Basic ' . base64_encode('test-user:test-password')
            );
    });

    expect($response['success'])->toBeTrue()
        ->and($response['status'])->toBe('QUEUED')
        ->and($response['reference'])->toBe('PH-TEST-123');
});

it('queries a PayHero transaction by provider reference', function () {
    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
    ]);

    Http::fake([
        'backend.payhero.co.ke/api/v2/transaction-status*' => Http::response([
            'success' => true,
            'status' => 'SUCCESS',
            'reference' => 'PH-TEST-123',
            'amount' => 100,
            'provider_reference' => 'QWE123456',
        ]),
    ]);

    $response = app(PayHeroApiClient::class)
        ->getTransactionStatus('PH-TEST-123');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://backend.payhero.co.ke/api/v2/transaction-status?reference=PH-TEST-123'
            && $request->method() === 'GET';
    });

    expect($response['success'])->toBeTrue()
        ->and($response['status'])->toBe('SUCCESS')
        ->and($response['provider_reference'])->toBe('QWE123456');
});