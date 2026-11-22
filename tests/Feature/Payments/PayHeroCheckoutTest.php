<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('customer can initiate an M-Pesa STK push for an eligible KES order', function () {
    Http::preventStrayRequests();
    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'success' => true,
            'status' => 'QUEUED',
            'reference' => 'PH-TEST-REFERENCE',
            'CheckoutRequestID' => 'ws_CO_TEST_123',
        ], 200),
    ]);

    config([
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
        'services.payhero.callback_url' => 'https://example.test/payhero/callback',
        'services.payhero.channel_id' => '12345',
    ]);

    $order = Order::factory()->create();
    $customer = User::query()->findOrFail($order->user_id);

    $response = $this->actingAs($customer)->post(route('orders.payments.store', $order), [
        'phone_number' => '254712345678',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');

    $payment = Payment::query()->where('order_id', $order->id)->sole();

    expect($payment->provider)->toBe('payhero')
        ->and($payment->payment_method)->toBe('mpesa')
        ->and($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->provider_checkout_request_id)->toBe('ws_CO_TEST_123')
        ->and($order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
});

test('customer cannot initiate checkout for another customers order', function () {
    $order = Order::factory()->create();
    $otherCustomer = User::factory()->create();

    $this->actingAs($otherCustomer)
        ->post(route('orders.payments.store', $order), [
            'phone_number' => '254712345678',
        ])
        ->assertNotFound();

    expect($order->payments()->count())->toBe(0);
});
