
<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\PayHeroStkPushService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('initiates a PayHero STK push and stores the provider identifiers', function () {
    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
        'services.payhero.callback_url' => 'https://example.com/payhero/callback',
    ]);

    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'success' => true,
            'status' => 'QUEUED',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
        ], 201),
    ]);

    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    $result = app(PayHeroStkPushService::class)
        ->initiate($payment, '254712345678');

expect($result->provider_transaction_reference)->toBe('PH-TEST-123')
    ->and($result->provider_reference)->toBeNull()
    ->and($result->provider_checkout_request_id)->toBe('checkout-123')
    ->and($result->provider_initiation_started_at)->toBeNull()
    ->and($result->next_reconciliation_at)->not->toBeNull();

    Http::assertSent(function (Request $request) use ($payment) {
        return $request->url() === 'https://backend.payhero.co.ke/api/v2/payments'
            && $request->method() === 'POST'
            && $request['amount'] === 100
            && $request['phone_number'] === '254712345678'
            && $request['channel_id'] === 12345
            && $request['provider'] === 'm-pesa'
            && $request['external_reference'] === $payment->reference
            && $request['callback_url'] === 'https://example.com/payhero/callback';
    });
});


it('rejects payments belonging to other providers', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments that are not in Kenyan shillings', function () {
    Http::fake();

    $currency = \App\Models\Currency::factory()->create([
        'code' => 'USD',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'currency_id' => $currency->id,
        'provider_channel_id' => '12345',
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects fractional payment amounts', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100.50,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments that are not pending', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'status' => Payment::STATUS_FAILED,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('does not initiate a second STK push for the same payment', function () {
    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'success' => true,
            'status' => 'QUEUED',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
        ], 201),
    ]);

    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
        'services.payhero.callback_url' => 'https://example.com/payhero/callback',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    $service = app(PayHeroStkPushService::class);

    $service->initiate($payment, '254712345678');

    expect(fn () => $service->initiate(
        $payment->fresh(),
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertSentCount(1);
});

it('rejects invalid Kenyan phone numbers', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '0712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments without a PayHero channel ID', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => null,
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects invalid PayHero channel IDs', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => 'abc123',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});

it('rejects payments that already have a checkout request ID', function () {
    Http::fake();

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'provider_checkout_request_id' => 'existing-checkout-123',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertNothingSent();
});


it('rejects an incomplete PayHero response', function () {
    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'success' => true,
            'status' => 'QUEUED',
            // Missing reference and CheckoutRequestID
        ], 201),
    ]);

    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
        'services.payhero.callback_url' => 'https://example.com/payhero/callback',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(RuntimeException::class)
    ->and($payment->provider_transaction_reference)->toBeNull();

    expect($payment->fresh())
        ->provider_reference->toBeNull()
        ->provider_checkout_request_id->toBeNull();

    Http::assertSentCount(1);
});

it('rejects an STK push when PayHero does not accept the request', function () {
    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'success' => false,
            'status' => 'FAILED',
            'message' => 'Unable to initiate payment.',
        ], 201),
    ]);

    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
        'services.payhero.callback_url' => 'https://example.com/payhero/callback',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(RuntimeException::class);

    expect($payment->fresh())
        ->provider_reference->toBeNull()
        ->provider_checkout_request_id->toBeNull();

    Http::assertSentCount(1);
});

it('propagates PayHero HTTP errors without persisting provider identifiers', function () {
    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'message' => 'Unauthorized',
        ], 401),
    ]);

    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'wrong-password',
        'services.payhero.callback_url' => 'https://example.com/payhero/callback',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(\Illuminate\Http\Client\RequestException::class);
    expect($payment->fresh()->provider_initiation_started_at)->toBeNull();

    expect($payment->fresh())
        ->provider_reference->toBeNull()
        ->provider_checkout_request_id->toBeNull();

    Http::assertSentCount(1);
});

it('retains the initiation claim after an ambiguous server error', function () {
    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'message' => 'Internal server error',
        ], 500),
    ]);

    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
        'services.payhero.callback_url' => 'https://example.com/payhero/callback',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(\Illuminate\Http\Client\RequestException::class);

    expect($payment->fresh()->provider_initiation_started_at)
        ->not->toBeNull();

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment->fresh(),
        '254712345678'
    ))->toThrow(InvalidArgumentException::class);

    Http::assertSentCount(1);
});

it('releases the initiation claim after a definite HTTP rejection', function () {
    Http::fake([
        'backend.payhero.co.ke/api/v2/payments' => Http::response([
            'message' => 'Invalid channel ID',
        ], 422),
    ]);

    config([
        'services.payhero.base_url' => 'https://backend.payhero.co.ke',
        'services.payhero.username' => 'test-user',
        'services.payhero.password' => 'test-password',
        'services.payhero.callback_url' => 'https://example.com/payhero/callback',
    ]);

    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'amount' => 100,
        'status' => Payment::STATUS_PENDING,
    ]);

    expect(fn () => app(PayHeroStkPushService::class)->initiate(
        $payment,
        '254712345678'
    ))->toThrow(\Illuminate\Http\Client\RequestException::class);

    expect($payment->fresh()->provider_initiation_started_at)->toBeNull();

    Http::assertSentCount(1);
});