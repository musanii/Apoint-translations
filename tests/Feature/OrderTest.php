<?php

use App\Models\Currency;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a quote', function () {
    $order = Order::factory()->create();

    expect($order->quote)->toBeInstanceOf(Quote::class);
});

it('belongs to a customer', function () {
    $order = Order::factory()->create();

    expect($order->user)->toBeInstanceOf(User::class);
});

it('belongs to a currency', function () {
    $order = Order::factory()->create();

    expect($order->currency)->toBeInstanceOf(Currency::class);
});

it('casts monetary fields to two decimal places', function () {
    $order = Order::factory()->create();

    expect($order->subtotal)->toBe('250.00')
        ->and($order->express_fee)->toBe('0.00')
        ->and($order->certification_fee)->toBe('0.00')
        ->and($order->courier_fee)->toBe('0.00')
        ->and($order->total)->toBe('250.00');
});

it('casts paid_at to a datetime', function () {
    $order = Order::factory()->create([
        'paid_at' => now(),
    ]);

    expect($order->paid_at)->toBeInstanceOf(CarbonInterface::class);
});

it('allows a customer to retrieve their orders', function () {
    $order = Order::factory()->create();

    $orders = $order->user->orders;

    expect($orders)->toHaveCount(1)
        ->and($orders->first()->id)->toBe($order->id);
});

it('does not expose another customers orders through their relationship', function () {
    $order = Order::factory()->create();

    $anotherUser = User::factory()->create();

    expect($anotherUser->orders)->toBeEmpty()
        ->and($order->user->orders)->toHaveCount(1);
});
