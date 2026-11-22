<?php

use App\Models\Currency;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the customer dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('summary.requests', 0)
            ->where('summary.quotesAwaitingDecision', 0)
            ->where('summary.activeOrders', 0)
            ->where('summary.completedOrders', 0)
            ->has('recentRequests', 0)
            ->has('recentOrders', 0)
        );
});

test('the dashboard only shows the signed in customers requests', function () {
    $customer = User::factory()->create();
    $anotherCustomer = User::factory()->create();

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

    $ownRequest = QuoteRequest::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'delivery_method' => 'digital',
    ]);
    QuoteRequest::create([
        'user_id' => $anotherCustomer->id,
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'delivery_method' => 'digital',
    ]);

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.requests', 1)
            ->has('recentRequests', 1)
            ->where('recentRequests.0.id', $ownRequest->id)
        );
});

test('internal draft quotations are not exposed on the customer dashboard', function () {
    $customer = User::factory()->create();
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
    $quoteRequest = QuoteRequest::create([
        'user_id' => $customer->id,
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'delivery_method' => 'digital',
    ]);
    Quote::factory()->create([
        'quote_request_id' => $quoteRequest->id,
        'currency_id' => $currency->id,
        'status' => Quote::STATUS_DRAFT,
    ]);

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('recentRequests.0.quotes', 0)
        );
});


test('an authenticated but unverified customer can access the dashboard', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dashboard'));
});

test('administrators are redirected to their dedicated dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('counts.totalRequests', 0)
            ->where('counts.orders', 0)
        );
});
