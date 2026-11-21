
<?php

use App\Models\Order;
use App\Models\Payment;

it('accepts a valid PayHero callback and returns HTTP 200', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => 'pending',
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $response = $this->postJson(route('payhero.callback'), [
        'status' => true,
        'response' => [
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'ResultCode' => 0,
            'Status' => 'Success',
            'Amount' => 100,
            'MpesaReceiptNumber' => 'RCP123456',
        ],
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Callback accepted.',
        ]);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->fresh()->provider_reference)
        ->toBe('RCP123456')
        ->and($order->fresh()->payment_status)
        ->toBe('paid');
});

it('acknowledges callbacks for unknown checkout requests', function () {
    $response = $this->postJson(route('payhero.callback'), [
        'status' => true,
        'response' => [
            'CheckoutRequestID' => 'unknown-checkout-123',
            'ExternalReference' => 'UNKNOWN-REFERENCE',
            'ResultCode' => 0,
            'Status' => 'Success',
            'Amount' => 100,
            'MpesaReceiptNumber' => 'RCP123456',
        ],
    ]);

    $response
        ->assertOk()
        ->assertJson([
            'status' => true,
            'message' => 'Callback accepted.',
        ]);
});

it('returns HTTP 500 when PayHero sends an invalid callback', function () {
    $response = $this->postJson(route('payhero.callback'), [
        'status' => true,
        'response' => [
            // Missing CheckoutRequestID.
            'ResultCode' => 0,
            'Status' => 'Success',
            'Amount' => 100,
            'MpesaReceiptNumber' => 'RCP123456',
        ],
    ]);

    $response
        ->assertStatus(500)
        ->assertJson([
            'status' => false,
            'message' => 'Callback processing failed.',
        ]);
});
