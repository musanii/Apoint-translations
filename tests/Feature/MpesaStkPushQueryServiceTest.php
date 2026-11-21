<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaStkPushQueryService;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

it('queries Daraja for the status of an existing STK Push', function () {
    config([
        'services.mpesa.sandbox' => true,
        'services.mpesa.shortcode' => '174379',
        'services.mpesa.passkey' => 'test-passkey',
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
            'ResponseCode' => '0',
            'ResponseDescription' => 'The service request has been accepted successfully',
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResultCode' => '0',
            'ResultDesc' => 'The service request is processed successfully.',
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
        'provider_checkout_request_id' => 'ws_CO_123456',
        'provider_merchant_request_id' => 'merchant-request-123',
    ]);

    $result = app(MpesaStkPushQueryService::class)->query($payment);

    expect($result['CheckoutRequestID'])
        ->toBe('ws_CO_123456')
        ->and($result['ResultCode'])
        ->toBe('0');

    Http::assertSent(function (Request $request) {
        if (! str_contains($request->url(), '/mpesa/stkpushquery/v1/query')) {
            return false;
        }

        return $request->method() === 'POST'
            && $request['BusinessShortCode'] === '174379'
            && $request['CheckoutRequestID'] === 'ws_CO_123456'
            && $request->hasHeader('Authorization', 'Bearer sandbox-access-token');
    });
});

it('rejects payments from other providers', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'flutterwave',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'ws_CO_123456',
    ]);

    expect(fn () => app(MpesaStkPushQueryService::class)->query($payment))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments that are not pending', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_checkout_request_id' => 'ws_CO_123456',
    ]);

    expect(fn () => app(MpesaStkPushQueryService::class)->query($payment))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments without a checkout request ID', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => null,
    ]);

    expect(fn () => app(MpesaStkPushQueryService::class)->query($payment))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects a query that Daraja does not accept', function () {
    config([
        'services.mpesa.sandbox' => true,
        'services.mpesa.shortcode' => '174379',
        'services.mpesa.passkey' => 'test-passkey',
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
            'ResponseCode' => '1',
            'ResponseDescription' => 'Invalid request',
        ], 200),
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'ws_CO_123456',
    ]);

    expect(fn () => app(MpesaStkPushQueryService::class)->query($payment))
        ->toThrow(RuntimeException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING);
});

it('does not change payment status when Daraja is unavailable', function () {
    config([
        'services.mpesa.sandbox' => true,
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
            'errorMessage' => 'Service unavailable',
        ], 503),
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'ws_CO_123456',
    ]);

    expect(fn () => app(MpesaStkPushQueryService::class)->query($payment))
        ->toThrow(RequestException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING);
});

it('verifies a late successful callback by querying Daraja for a failed payment', function () {
    config([
        'services.mpesa.sandbox' => true,
        'services.mpesa.shortcode' => '174379',
        'services.mpesa.passkey' => 'test-passkey',
    ]);

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ], 200),

        'sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query' => Http::response([
            'ResponseCode' => '0',
            'ResponseDescription' => 'The service request has been accepted successfully',
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResultCode' => '0',
            'ResultDesc' => 'The service request is processed successfully.',
        ], 200),
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_checkout_request_id' => 'ws_CO_123456',
        'provider_merchant_request_id' => 'merchant-request-123',
    ]);

    $result = app(MpesaStkPushQueryService::class)
        ->verifyLateSuccess($payment);

    expect($result['CheckoutRequestID'])
        ->toBe('ws_CO_123456')
        ->and($result['ResultCode'])
        ->toBe('0');

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED);

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), '/mpesa/stkpushquery/v1/query')
            && $request->method() === 'POST'
            && $request['CheckoutRequestID'] === 'ws_CO_123456';
    });
});

it('rejects late-success verification for a pending payment', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_checkout_request_id' => 'ws_CO_123456',
    ]);

    expect(fn () => app(MpesaStkPushQueryService::class)
        ->verifyLateSuccess($payment))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects late-success verification for payments from other providers', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'flutterwave',
        'status' => Payment::STATUS_FAILED,
        'provider_checkout_request_id' => 'ws_CO_123456',
    ]);

    expect(fn () => app(MpesaStkPushQueryService::class)
        ->verifyLateSuccess($payment))
        ->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});
