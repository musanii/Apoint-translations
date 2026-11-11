<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use App\Services\MpesaAccessTokenService;

it('retrieves an access token from the Daraja sandbox', function () {
    config([
        'services.mpesa.base_url' => 'https://sandbox.safaricom.co.ke',
        'services.mpesa.consumer_key' => 'test-consumer-key',
        'services.mpesa.consumer_secret' => 'test-consumer-secret',
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ], 200),
    ]);

    $token = app(MpesaAccessTokenService::class)->getAccessToken();

    expect($token)->toBe('sandbox-access-token');

    Http::assertSent(function (Request $request) {
        return $request->method() === 'GET'
            && $request->url() === 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            && $request->hasHeader(
                'Authorization',
                'Basic ' . base64_encode('test-consumer-key:test-consumer-secret')
            );
    });
});

it('reuses the cached access token', function () {
    config([
        'services.mpesa.sandbox' => true,
        'services.mpesa.consumer_key' => 'test-consumer-key',
        'services.mpesa.consumer_secret' => 'test-consumer-secret',
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'cached-sandbox-token',
            'expires_in' => '3599',
        ], 200),
    ]);

    $service = app(MpesaAccessTokenService::class);

    expect($service->getAccessToken())->toBe('cached-sandbox-token');
    expect($service->getAccessToken())->toBe('cached-sandbox-token');

    Http::assertSentCount(1);
});