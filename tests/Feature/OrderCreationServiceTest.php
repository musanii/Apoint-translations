<?php

use App\Models\Order;
use App\Models\Quote;
use App\Services\OrderCreationService;
use App\Services\OrderReferenceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;

uses(RefreshDatabase::class);

it('creates an order when a sent quote is accepted', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
        'subtotal' => 250,
        'express_fee' => 50,
        'certification_fee' => 30,
        'courier_fee' => 100,
        'total' => 430,
    ]);

    $service = app(OrderCreationService::class);

    $order = $service->createFromAcceptedQuote($quote);

    expect($quote->fresh()->status)->toBe(Quote::STATUS_ACCEPTED)
        ->and($quote->fresh()->accepted_at)->not->toBeNull()
        ->and($order)->toBeInstanceOf(Order::class)
        ->and($order->quote_id)->toBe($quote->id)
        ->and($order->subtotal)->toBe('250.00')
        ->and($order->express_fee)->toBe('50.00')
        ->and($order->certification_fee)->toBe('30.00')
        ->and($order->courier_fee)->toBe('100.00')
        ->and($order->total)->toBe('430.00')
        ->and($order->status)->toBe(Order::STATUS_PENDING_PAYMENT)
        ->and($order->payment_status)->toBe(Order::PAYMENT_STATUS_UNPAID);

    expect(Order::where('quote_id', $quote->id)->count())->toBe(1);
});

it('does not create another order from an already accepted quote', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_ACCEPTED,
    ]);

    $service = app(OrderCreationService::class);

    expect(fn () => $service->createFromAcceptedQuote($quote))
        ->toThrow(InvalidArgumentException::class);

    expect(Order::where('quote_id', $quote->id)->count())->toBe(0);
});

it('rolls back quote acceptance if order creation fails', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
    ]);

    $this->mock(OrderReferenceGenerator::class)
        ->shouldReceive('generate')
        ->once()
        ->andThrow(new RuntimeException('Reference generation failed.'));

    $service = app(OrderCreationService::class);

    expect(fn () => $service->createFromAcceptedQuote($quote))
        ->toThrow(RuntimeException::class);

    expect($quote->fresh()->status)->toBe(Quote::STATUS_SENT)
        ->and($quote->fresh()->accepted_at)->toBeNull()
        ->and(Order::where('quote_id', $quote->id)->count())->toBe(0);
});

it('copies the accepted quote and delivery details into the order snapshot', function () {
    $quote = Quote::factory()->create([
        'status' => Quote::STATUS_SENT,
        'subtotal' => 500,
        'express_fee' => 100,
        'certification_fee' => 50,
        'courier_fee' => 200,
        'total' => 850,
    ]);

    $quoteRequest = $quote->quoteRequest;

    $quoteRequest->update([
        'delivery_method' => 'physical',
        'recipient_name' => 'Jane Doe',
        'recipient_phone' => '+254712345678',
        'delivery_country' => 'Kenya',
        'delivery_address' => 'Westlands, Nairobi',
        'delivery_postal_code' => '00100',
    ]);

    $service = app(OrderCreationService::class);

    $order = $service->createFromAcceptedQuote($quote);

    expect($order->subtotal)->toBe('500.00')
        ->and($order->express_fee)->toBe('100.00')
        ->and($order->certification_fee)->toBe('50.00')
        ->and($order->courier_fee)->toBe('200.00')
        ->and($order->total)->toBe('850.00')
        ->and($order->delivery_method)->toBe('physical')
        ->and($order->recipient_name)->toBe('Jane Doe')
        ->and($order->recipient_phone)->toBe('+254712345678')
        ->and($order->delivery_country)->toBe('Kenya')
        ->and($order->delivery_address)->toBe('Westlands, Nairobi')
        ->and($order->delivery_postal_code)->toBe('00100');
});
