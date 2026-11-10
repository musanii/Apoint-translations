<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to an order', function () {
    $payment = Payment::factory()->create();

    expect($payment->order)
        ->toBeInstanceOf(Order::class)
        ->and($payment->order->id)
        ->toBe($payment->order_id);
});

it('casts the payment amount to two decimal places', function () {
    $payment = Payment::factory()->create([
        'amount' => 1250,
    ]);

    expect($payment->amount)->toBe('1250.00');
});

it('casts paid_at to a datetime', function () {
    $payment = Payment::factory()->create([
        'paid_at' => now(),
    ]);

    expect($payment->paid_at)
        ->toBeInstanceOf(\Carbon\CarbonInterface::class);
});

it('allows an order to have multiple payment attempts', function () {
    $order = Order::factory()->create();

    Payment::factory()->count(2)->create([
        'order_id' => $order->id,
    ]);

    expect($order->payments)->toHaveCount(2)
        ->and($order->payments->first())
        ->toBeInstanceOf(Payment::class);
});