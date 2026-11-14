<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaPaymentReconciliationService;
use Illuminate\Support\Facades\Http;

it('marks a payment successful when Daraja confirms the STK Push', function () {
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

    $result = app(MpesaPaymentReconciliationService::class)
        ->reconcile($payment);

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBeNull()
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($order->fresh()->status)
        ->toBe('confirmed');
});

it('marks a payment failed when Daraja confirms the STK Push failed', function () {
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
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResultCode' => '1032',
            'ResultDesc' => 'Request cancelled by user',
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

    $result = app(\App\Services\MpesaPaymentReconciliationService::class)
        ->reconcile($payment);

    expect($result->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($result->failure_reason)
        ->toBe('Request cancelled by user')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('does not change payment status when Daraja returns no result code', function () {
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
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResponseDescription' => 'The service request has been accepted successfully',
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

    expect(fn() => app(\App\Services\MpesaPaymentReconciliationService::class)
        ->reconcile($payment))
        ->toThrow(RuntimeException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PENDING);
});


it('does not update a payment when Daraja returns mismatched request identifiers', function () {
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
            'MerchantRequestID' => 'different-merchant-request',
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

    expect(fn() => app(\App\Services\MpesaPaymentReconciliationService::class)
        ->reconcile($payment))
        ->toThrow(RuntimeException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PENDING);
});

it('uses a fallback failure reason when Daraja returns an invalid description', function () {
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
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResultCode' => '1032',
            'ResultDesc' => ['unexpected' => 'value'],
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

    $result = app(\App\Services\MpesaPaymentReconciliationService::class)
        ->reconcile($payment);

    expect($result->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($result->failure_reason)
        ->toBe('M-Pesa payment failed.');
});

it('keeps a payment pending when Daraja returns an unrecognized result code', function () {
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
            'MerchantRequestID' => 'merchant-request-123',
            'CheckoutRequestID' => 'ws_CO_123456',
            'ResultCode' => '9999',
            'ResultDesc' => 'An unrecognized transaction result.',
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

    $result = app(MpesaPaymentReconciliationService::class)
        ->reconcile($payment);

    expect($result->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($result->failure_reason)
        ->toBeNull()
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PENDING);
});

it('rejects PayHero payments', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(MpesaPaymentReconciliationService::class)
        ->reconcile($payment))
        ->toThrow(InvalidArgumentException::class);
});
