<?php

use App\Mail\ApointNotificationMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayHeroCallbackService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;

uses(DatabaseMigrations::class);

function makePayHeroCallbackForDelivery(Payment $payment): array
{
    return [
        'status' => true,
        'response' => [
            'CheckoutRequestID' => $payment->provider_checkout_request_id,
            'ExternalReference' => $payment->reference,
            'ResultCode' => 0,
            'Status' => 'Success',
            'Amount' => (float) $payment->amount,
            'MpesaReceiptNumber' => 'DELIVERY'.fake()->unique()->numerify('######'),
        ],
    ];
}

it('creates payment notifications and sends one email for a successful callback only once', function () {
    Mail::fake();

    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'currency_id' => $order->currency_id,
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_channel_id' => '12345',
        'provider_transaction_reference' => 'PH-DELIVERY-REF',
        'provider_checkout_request_id' => 'ws_CO_DELIVERY_'.fake()->unique()->numerify('######'),
        'amount' => $order->total,
        'status' => Payment::STATUS_PENDING,
    ]);

    $payload = makePayHeroCallbackForDelivery($payment);
    $callback = app(PayHeroCallbackService::class);

    $callback->handle($payload);
    $callback->handle($payload);

    $customer = User::query()->findOrFail($order->user_id);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($order->fresh()->payment_status)->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($customer->notifications()->count())->toBe(1);

    Mail::assertSent(ApointNotificationMail::class, 1);
});
