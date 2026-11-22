<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrderAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

it('protects the admin order inbox from customers and guests', function () {
    $admin = makeOrderAdmin();
    $customer = User::factory()->create();

    $this->get(route('admin.orders.index'))->assertRedirect(route('login'));
    $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.orders.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/orders/index'));
});

it('allows administrators to move a paid order through fulfilment', function () {
    $admin = makeOrderAdmin();
    $order = Order::factory()->create([
        'status' => Order::STATUS_CONFIRMED,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.start-processing', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(Order::STATUS_IN_PROGRESS);

    $this->post(route('admin.orders.complete', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(Order::STATUS_COMPLETED);
});

it('allows cancellation only for unpaid or failed orders still awaiting payment', function () {
    $admin = makeOrderAdmin();
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_UNPAID,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.cancel', $order))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
});

it('does not cancel an order while payment is pending', function () {
    $admin = makeOrderAdmin();
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.orders.cancel', $order))
        ->assertSessionHasErrors('order');

    expect($order->fresh()->status)->toBe(Order::STATUS_PENDING_PAYMENT);
});
