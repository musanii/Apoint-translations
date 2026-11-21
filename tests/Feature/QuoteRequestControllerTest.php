<?php

use App\Models\Currency;
use App\Models\Language;
use App\Models\PricingRule;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function createQuoteRequestOptions(): array
{
    $service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $currency = Currency::create([
        'code' => 'KES',
        'name' => 'Kenyan Shilling',
        'symbol' => 'KSh',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => true,
    ]);

    $english = Language::create([
        'name' => 'English',
        'code' => 'en',
        'is_active' => true,
    ]);

    $swahili = Language::create([
        'name' => 'Swahili',
        'code' => 'sw',
        'is_active' => true,
    ]);

    return compact('service', 'currency', 'english', 'swahili');
}

test('guests are redirected to login before opening the quote request form', function () {
    $this->get(route('quote-requests.create'))
        ->assertRedirect(route('login'));
});

test('an authenticated customer can open the quote request form with active options', function () {
    $user = User::factory()->create();
    $options = createQuoteRequestOptions();

    $this->actingAs($user)
        ->get(route('quote-requests.create', [
            'service_id' => $options['service']->id,
            'source_language_id' => $options['english']->id,
            'target_language_id' => $options['swahili']->id,
            'currency_id' => $options['currency']->id,
            'quantity' => 450,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('quote-requests/create')
            ->where('initial.service_id', $options['service']->id)
            ->where('initial.source_language_id', $options['english']->id)
            ->where('initial.target_language_id', $options['swahili']->id)
            ->where('initial.currency_id', $options['currency']->id)
            ->where('initial.quantity', 450)
        );
});

test('an authenticated customer can submit a digital quote request without a pricing rule', function () {
    $user = User::factory()->create();
    $options = createQuoteRequestOptions();

    $this->actingAs($user)
        ->post(route('quote-requests.store'), [
            'service_id' => $options['service']->id,
            'source_language_id' => $options['english']->id,
            'target_language_id' => $options['swahili']->id,
            'currency_id' => $options['currency']->id,
            'quantity' => 450,
            'delivery_date' => now()->addDays(5)->toDateString(),
            'instructions' => 'Please preserve the original formatting.',
            'delivery_method' => 'digital',
        ])
        ->assertRedirect();

    $quoteRequest = QuoteRequest::query()->sole();

    expect($quoteRequest->user_id)->toBe($user->id)
        ->and($quoteRequest->service_id)->toBe($options['service']->id)
        ->and($quoteRequest->quantity)->toBe(450)
        ->and($quoteRequest->unit)->toBeNull()
        ->and($quoteRequest->status)->toBe('pending')
        ->and($quoteRequest->delivery_method)->toBe('digital')
        ->and($quoteRequest->recipient_name)->toBeNull();

    $this->assertDatabaseCount('quotes', 0);
});

test('a matching active pricing rule supplies the request quantity unit', function () {
    $user = User::factory()->create();
    $options = createQuoteRequestOptions();

    PricingRule::create([
        'service_id' => $options['service']->id,
        'currency_id' => $options['currency']->id,
        'source_language_id' => $options['english']->id,
        'target_language_id' => $options['swahili']->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 8,
        'minimum_charge' => 800,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('quote-requests.store'), [
            'service_id' => $options['service']->id,
            'source_language_id' => $options['english']->id,
            'target_language_id' => $options['swahili']->id,
            'currency_id' => $options['currency']->id,
            'quantity' => 450,
            'delivery_method' => 'digital',
        ])
        ->assertRedirect();

    $quoteRequest = QuoteRequest::query()->sole();

    expect($quoteRequest->quantity)->toBe(450)
        ->and($quoteRequest->unit)->toBe('word');
});

test('a custom language combination can use an unambiguous service quantity unit', function () {
    $user = User::factory()->create();
    $options = createQuoteRequestOptions();

    $french = Language::create([
        'name' => 'French',
        'code' => 'fr',
        'is_active' => true,
    ]);

    $german = Language::create([
        'name' => 'German',
        'code' => 'de',
        'is_active' => true,
    ]);

    PricingRule::create([
        'service_id' => $options['service']->id,
        'currency_id' => $options['currency']->id,
        'source_language_id' => $options['english']->id,
        'target_language_id' => $options['swahili']->id,
        'pricing_type' => 'per_word',
        'unit' => 'word',
        'rate' => 8,
        'minimum_charge' => 800,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->post(route('quote-requests.store'), [
            'service_id' => $options['service']->id,
            'source_language_id' => $french->id,
            'target_language_id' => $german->id,
            'currency_id' => $options['currency']->id,
            'quantity' => 450,
            'delivery_method' => 'digital',
        ])
        ->assertRedirect();

    $quoteRequest = QuoteRequest::query()->sole();

    expect($quoteRequest->quantity)->toBe(450)
        ->and($quoteRequest->unit)->toBe('word');

    $this->assertDatabaseCount('quotes', 0);
});

test('a customer can submit a request with physical delivery details', function () {
    $user = User::factory()->create();
    $options = createQuoteRequestOptions();

    $this->actingAs($user)
        ->post(route('quote-requests.store'), [
            'service_id' => $options['service']->id,
            'source_language_id' => $options['english']->id,
            'target_language_id' => $options['swahili']->id,
            'currency_id' => $options['currency']->id,
            'delivery_method' => 'physical',
            'recipient_name' => 'Jane Doe',
            'recipient_phone' => '+254712345678',
            'delivery_country' => 'Kenya',
            'delivery_address' => 'Westlands, Nairobi',
            'delivery_postal_code' => '00800',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('quote_requests', [
        'user_id' => $user->id,
        'delivery_method' => 'physical',
        'recipient_name' => 'Jane Doe',
        'recipient_phone' => '+254712345678',
        'delivery_country' => 'Kenya',
        'delivery_address' => 'Westlands, Nairobi',
        'delivery_postal_code' => '00800',
    ]);
});

test('a customer cannot submit a request using inactive options', function () {
    $user = User::factory()->create();
    $options = createQuoteRequestOptions();
    $options['service']->update(['is_active' => false]);

    $this->actingAs($user)
        ->from(route('quote-requests.create'))
        ->post(route('quote-requests.store'), [
            'service_id' => $options['service']->id,
            'source_language_id' => $options['english']->id,
            'target_language_id' => $options['swahili']->id,
            'currency_id' => $options['currency']->id,
            'delivery_method' => 'digital',
        ])
        ->assertSessionHasErrors('service_id');

    $this->assertDatabaseCount('quote_requests', 0);
});

test('a customer can only view their own quote request', function () {
    $owner = User::factory()->create();
    $anotherUser = User::factory()->create();
    $options = createQuoteRequestOptions();

    $quoteRequest = $owner->quoteRequests()->create([
        'service_id' => $options['service']->id,
        'currency_id' => $options['currency']->id,
        'delivery_method' => 'digital',
    ]);

    $this->actingAs($anotherUser)
        ->get(route('quote-requests.show', $quoteRequest))
        ->assertForbidden();
});
