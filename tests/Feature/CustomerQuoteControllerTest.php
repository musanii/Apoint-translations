<?php

use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('a customer can view a sent quotation with its price breakdown', function () {
    $quote = Quote::factory()->create(['status' => Quote::STATUS_SENT, 'sent_at' => now()]);
    $this->actingAs($quote->quoteRequest->user)->get(route('quotes.show', $quote))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('quotes/show')
            ->where('quote.reference', $quote->reference)
            ->where('quote.total', $quote->total)
            ->where('quote.status', Quote::STATUS_SENT));
});

test('a customer cannot view another customers quotation', function () {
    $quote = Quote::factory()->create(['status' => Quote::STATUS_SENT]);
    $this->actingAs(User::factory()->create())->get(route('quotes.show', $quote))->assertNotFound();
});

test('customers cannot view internal draft quotations', function () {
    $quote = Quote::factory()->create(['status' => Quote::STATUS_DRAFT]);
    $this->actingAs($quote->quoteRequest->user)->get(route('quotes.show', $quote))->assertNotFound();
});

test('accepting a sent quotation creates one pending-payment order', function () {
    $quote = Quote::factory()->create(['status' => Quote::STATUS_SENT, 'sent_at' => now()]);
    $this->actingAs($quote->quoteRequest->user)->post(route('quotes.accept', $quote))->assertRedirect();
    expect($quote->fresh()->status)->toBe(Quote::STATUS_ACCEPTED);
    $this->assertDatabaseHas('orders', [
        'quote_id' => $quote->id, 'user_id' => $quote->quoteRequest->user_id,
        'status' => Order::STATUS_PENDING_PAYMENT, 'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);
    $this->assertDatabaseCount('orders', 1);
});

test('a customer cannot accept another customers quotation', function () {
    $quote = Quote::factory()->create(['status' => Quote::STATUS_SENT]);
    $this->actingAs(User::factory()->create())->post(route('quotes.accept', $quote))->assertNotFound();
    $this->assertDatabaseCount('orders', 0);
});

test('a customer can decline a sent quotation', function () {
    $quote = Quote::factory()->create(['status' => Quote::STATUS_SENT]);
    $this->actingAs($quote->quoteRequest->user)->post(route('quotes.reject', $quote))->assertRedirect(route('dashboard'));
    expect($quote->fresh()->status)->toBe(Quote::STATUS_REJECTED)->and($quote->fresh()->rejected_at)->not->toBeNull();
});

test('an expired quotation cannot be accepted', function () {
    $quote = Quote::factory()->create(['status' => Quote::STATUS_SENT, 'valid_until' => now()->subDay()]);
    $this->actingAs($quote->quoteRequest->user)->post(route('quotes.accept', $quote))->assertRedirect(route('dashboard'));
    expect($quote->fresh()->status)->toBe(Quote::STATUS_EXPIRED);
    $this->assertDatabaseCount('orders', 0);
});
