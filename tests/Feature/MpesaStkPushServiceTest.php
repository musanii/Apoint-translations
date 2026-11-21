<?php

use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaStkPushService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('initiates an STK push and stores the Daraja request identifiers', function () {
    config([
        'services.mpesa.sandbox' => true,
        'services.mpesa.shortcode' => '174379',
        'services.mpesa.passkey' => 'test-passkey',
        'services.mpesa.callback_url' => 'https://example.com/mpesa/callback',
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success. Request accepted for processing',
            'CustomerMessage' => 'Success. Request accepted for processing',
        ], 200),
    ]);

    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    $result = app(MpesaStkPushService::class)->initiate($payment, '254712345678');

    expect($result->provider_checkout_request_id)->toBe('ws_CO_123456');
    expect($result->provider_merchant_request_id)->toBe('merchant-request-123');

    Http::assertSent(function (Request $request) {
        if (! str_contains($request->url(), '/mpesa/stkpush/v1/processrequest')) {
            return false;
        }

        return $request->method() === 'POST'
            && $request['BusinessShortCode'] === '174379'
            && $request['PhoneNumber'] === '254712345678'
            && $request['Amount'] === 100
            && $request['CallBackURL'] === 'https://example.com/mpesa/callback'
            && $request->hasHeader('Authorization', 'Bearer sandbox-access-token');
    });
});

it('does not initiate a second STK Push for the same payment', function () {
    config([
        'services.mpesa.sandbox' => true,
        'services.mpesa.shortcode' => '174379',
        'services.mpesa.passkey' => 'test-passkey',
        'services.mpesa.callback_url' => 'https://example.com/mpesa/callback',
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResponseCode' => '0',
        ], 200),
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    $service = app(MpesaStkPushService::class);

    $service->initiate($payment, '254712345678');

    expect(fn () => $service->initiate(
        $payment->fresh(),
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertSentCount(2);
});

it('does not initiate an STK Push for a failed payment', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
    ]);

    expect(fn () => app(MpesaStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments from other providers', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'flutterwave',
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(MpesaStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments that are not in Kenyan shillings', function () {
    Http::fake();

    $currency = Currency::factory()->create([
        'code' => 'USD',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'currency_id' => $currency->id,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(MpesaStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects fractional payment amounts', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100.50,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(MpesaStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects PayHero payments', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(MpesaStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});
