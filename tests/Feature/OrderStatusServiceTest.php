<?php

use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

it('confirms an order after successful payment', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $service = app(OrderStatusService::class);

    $service->confirm($order);

    expect($order->fresh()->status)
        ->toBe(Order::STATUS_CONFIRMED);
});

it('starts processing a confirmed order', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $service = app(OrderStatusService::class);

    $service->startProcessing($order);

    expect($order->fresh()->status)
        ->toBe(Order::STATUS_IN_PROGRESS);
});

it('completes an order that is in progress', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_IN_PROGRESS,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $service = app(OrderStatusService::class);

    $service->complete($order);

    expect($order->fresh()->status)
        ->toBe(Order::STATUS_COMPLETED);
});

it('cancels an order awaiting payment', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);

    $service = app(OrderStatusService::class);

    $service->cancel($order);

    expect($order->fresh()->status)
        ->toBe(Order::STATUS_CANCELLED);
});

it('does not allow a paid confirmed order to be cancelled outside the refund process', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    expect(fn () => app(OrderStatusService::class)->cancel($order))
        ->toThrow(InvalidArgumentException::class);

    expect($order->fresh()->status)->toBe(Order::STATUS_CONFIRMED);
});

it('does not allow an order in progress to be cancelled', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_IN_PROGRESS,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $service = app(OrderStatusService::class);

    expect(fn () => $service->cancel($order))
        ->toThrow(InvalidArgumentException::class);

    expect($order->fresh()->status)
        ->toBe(Order::STATUS_IN_PROGRESS);
});

it('does not allow a completed order to be cancelled', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_COMPLETED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $service = app(OrderStatusService::class);

    expect(fn () => $service->cancel($order))
        ->toThrow(InvalidArgumentException::class);

    expect($order->fresh()->status)
        ->toBe(Order::STATUS_COMPLETED);
});

it('does not confirm an unpaid order', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);

    $service = app(OrderStatusService::class);

    expect(fn () => $service->confirm($order))
        ->toThrow(InvalidArgumentException::class);

    expect($order->fresh()->status)
        ->toBe(Order::STATUS_PENDING_PAYMENT);
});
