<?php

use App\Models\Order;
use App\Models\Payment;

function payHeroPendingPayment(): Payment
{
    $order = Order::factory()->create();

    return Payment::factory()->create([
        'order_id' => $order->id,
        'currency_id' => $order->currency_id,
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'provider_transaction_reference' => 'PH-TEST-REFERENCE',
        'provider_checkout_request_id' => 'ws_CO_TEST_'.fake()->unique()->numerify('######'),
        'amount' => $order->total,
        'status' => Payment::STATUS_PENDING,
    ]);
}

function payHeroCallback(Payment $payment, int $resultCode, string $status, array $extra = []): array
{
    return [
        'status' => true,
        'response' => array_merge([
            'CheckoutRequestID' => $payment->provider_checkout_request_id,
            'ExternalReference' => $payment->reference,
            'ResultCode' => $resultCode,
            'Status' => $status,
            'Amount' => (float) $payment->amount,
        ], $extra),
    ];
}

test('successful PayHero callback marks the payment and order paid', function () {
    $payment = payHeroPendingPayment();

    $this->postJson(route('payhero.callback'), payHeroCallback(
        $payment,
        0,
        'Success',
        ['MpesaReceiptNumber' => 'QWE123456'],
    ))->assertOk()->assertJson(['status' => true]);

    $payment->refresh();
    $order = $payment->order->fresh();

    expect($payment->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->provider_reference)->toBe('QWE123456')
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($order->status)->toBe(Order::STATUS_CONFIRMED);
});

test('repeated successful PayHero callbacks do not duplicate payment records', function () {
    $payment = payHeroPendingPayment();
    $payload = payHeroCallback($payment, 0, 'Success', [
        'MpesaReceiptNumber' => 'QWE654321',
    ]);

    $this->postJson(route('payhero.callback'), $payload)->assertOk();
    $this->postJson(route('payhero.callback'), $payload)->assertOk();

    expect(Payment::query()->where('order_id', $payment->order_id)->count())->toBe(1)
        ->and($payment->fresh()->provider_reference)->toBe('QWE654321');
});

test('failed PayHero callback never marks an order as paid', function () {
    $payment = payHeroPendingPayment();

    $this->postJson(route('payhero.callback'), payHeroCallback(
        $payment,
        1032,
        'Failed',
        ['ResultDesc' => 'The request was cancelled by the user.'],
    ))->assertOk()->assertJson(['status' => true]);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($payment->order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_FAILED)
        ->and($payment->order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});
