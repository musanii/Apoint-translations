<?php

use App\\Models\\Order;
use App\\Models\\Payment;

function darajaPendingPayment(): Payment
{
    $order = Order::factory()->create();

    return Payment::factory()->create([
        'order_id' => $order->id,
        'currency_id' => $order->currency_id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'provider_merchant_request_id' => 'MERCHANT-TEST-123',
        'provider_checkout_request_id' => 'ws_CO_DARAJA_TEST_123',
        'amount' => $order->total,
        'status' => Payment::STATUS_PENDING,
    ]);
}

function darajaCallback(Payment $payment, int $resultCode, array $metadata = []): array
{
    $stkCallback = [
        'MerchantRequestID' => $payment->provider_merchant_request_id,
        'CheckoutRequestID' => $payment->provider_checkout_request_id,
        'ResultCode' => $resultCode,
        'ResultDesc' => $resultCode === 0 ? 'The service request is processed successfully.' : 'The request was cancelled by the user.',
    ];

    if ($resultCode === 0) {
        $stkCallback['CallbackMetadata'] = [
            'Item' => array_merge([
                ['Name' => 'Amount', 'Value' => (float) $payment->amount],
                ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCP123456'],
                ['Name' => 'TransactionDate', 'Value' => 20261122123000],
                ['Name' => 'PhoneNumber', 'Value' => 254712345678],
            ], $metadata),
        ];
    }

    return ['Body' => ['stkCallback' => $stkCallback]];
}

test('successful Daraja callback marks payment and order paid', function () {
    $payment = darajaPendingPayment();

    $this->postJson(route('mpesa.callback'), darajaCallback($payment, 0))
        ->assertOk()
        ->assertJson(['ResultCode' => 0]);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->fresh()->provider_reference)->toBe('RCP123456')
        ->and($payment->order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($payment->order->fresh()->status)->toBe(Order::STATUS_CONFIRMED);
});

test('duplicate successful Daraja callback is idempotent', function () {
    $payment = darajaPendingPayment();
    $payload = darajaCallback($payment, 0);

    $this->postJson(route('mpesa.callback'), $payload)->assertOk();
    $this->postJson(route('mpesa.callback'), $payload)->assertOk();

    expect(Payment::query()->where('order_id', $payment->order_id)->count())->toBe(1)
        ->and($payment->fresh()->provider_reference)->toBe('RCP123456');
});

test('failed Daraja callback never marks the order paid', function () {
    $payment = darajaPendingPayment();

    $this->postJson(route('mpesa.callback'), darajaCallback($payment, 1032))
        ->assertOk()
        ->assertJson(['ResultCode' => 0]);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($payment->order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_FAILED)
        ->and($payment->order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});

test('Daraja callback rejects a mismatched merchant request ID', function () {
    $payment = darajaPendingPayment();
    $payload = darajaCallback($payment, 0);
    $payload['Body']['stkCallback']['MerchantRequestID'] = 'WRONG-MERCHANT-ID';

    $this->postJson(route('mpesa.callback'), $payload)
        ->assertStatus(500);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
});
