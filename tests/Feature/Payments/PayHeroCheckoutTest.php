<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function configureDarajaCheckout(): void
{
    config([
        'services.mpesa.consumer_key' => 'sandbox-key',
        'services.mpesa.consumer_secret' => 'sandbox-secret',
        'services.mpesa.shortcode' => '174379',
        'services.mpesa.passkey' => 'sandbox-passkey',
        'services.mpesa.callback_url' => 'https://example.test/mpesa/callback',
        'services.mpesa.sandbox' => true,
    ]);
}

test('eligible order view exposes Daraja checkout when configured', function () {
    configureDarajaCheckout();

    $order = Order::factory()->create();
    $customer = User::query()->findOrFail($order->user_id);

    $this->actingAs($customer)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/show')
            ->where('canPayOnline', true)
        );
});

test('customer can initiate a Daraja M-Pesa STK push for an eligible KES order', function () {
    Http::preventStrayRequests();
    Cache::forget('mpesa_access_token');
    configureDarajaCheckout();

    Http::fake([
        'sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
            'access_token' => 'sandbox-access-token',
            'expires_in' => '3599',
        ]),
        'sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success. Request accepted for processing',
            'MerchantRequestID' => '29115-34620561-1',
            'CheckoutRequestID' => 'ws_CO_TEST_123',
        ]),
    ]);

    $order = Order::factory()->create();
    $customer = User::query()->findOrFail($order->user_id);

    $response = $this->actingAs($customer)->post(route('orders.payments.store', $order), [
        'phone_number' => '254712345678',
    ]);

    $response->assertRedirect(route('orders.show', $order));
    $response->assertSessionHas('success');

    $payment = Payment::query()->where('order_id', $order->id)->sole();

    expect($payment->provider)->toBe('daraja')
        ->and($payment->payment_method)->toBe('mpesa')
        ->and($payment->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->provider_checkout_request_id)->toBe('ws_CO_TEST_123')
        ->and($payment->provider_merchant_request_id)->toBe('29115-34620561-1')
        ->and($payment->provider_initiation_started_at)->toBeNull()
        ->and($order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PENDING);
});

test('PayHero credentials alone do not enable customer checkout while PayHero is paused', function () {
    config([
        'services.payhero.username' => 'configured-user',
        'services.payhero.password' => 'configured-password',
        'services.payhero.callback_url' => 'https://example.test/payhero/callback',
        'services.payhero.channel_id' => '12345',
        'services.mpesa.consumer_key' => null,
        'services.mpesa.consumer_secret' => null,
        'services.mpesa.shortcode' => null,
        'services.mpesa.passkey' => null,
        'services.mpesa.callback_url' => null,
    ]);

    $order = Order::factory()->create();
    $customer = User::query()->findOrFail($order->user_id);

    $this->actingAs($customer)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('orders/show')
            ->where('canPayOnline', false)
        );
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
