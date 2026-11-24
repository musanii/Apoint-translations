<?php

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function paymentReconciliationAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

it('protects the payment reconciliation ledger from customers and guests', function () {
    $admin = paymentReconciliationAdmin();
    $customer = User::factory()->create();

    $this->get(route('admin.payments.reconciliation'))
        ->assertRedirect(route('login'));

    $this->actingAs($customer)
        ->get(route('admin.payments.reconciliation'))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('admin.payments.reconciliation'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/payments/reconciliation')
            ->has('stats.total')
            ->has('payments.data'));
});

it('shows payment references, customer details and reconciliation exceptions', function () {
    $admin = paymentReconciliationAdmin();
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'provider_reference' => null,
        'status' => Payment::STATUS_SUCCESSFUL,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payments.reconciliation'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('payments.data.0.reference', $payment->reference)
            ->where('payments.data.0.attention_reason', 'Provider receipt reference not yet attached')
            ->where('stats.successful', 1)
            ->where('stats.attention', 1));
});

it('filters the ledger to payments that need attention', function () {
    $admin = paymentReconciliationAdmin();
    $order = Order::factory()->create();
    $needsAttention = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'created_at' => now()->subMinutes(20),
    ]);

    $recentOrder = Order::factory()->create();
    Payment::factory()->create([
        'order_id' => $recentOrder->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payments.reconciliation', ['attention' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('payments.data', 1)
            ->where('payments.data.0.id', $needsAttention->id));
});


it('displays the recorded reconciliation audit trail for a payment', function () {
    $admin = paymentReconciliationAdmin();
    $order = Order::factory()->create([
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'provider_reference' => 'AUDIT123',
    ]);

    PaymentReconciliationEvent::query()->create([
        'payment_id' => $payment->id,
        'event_type' => 'automatic_check',
        'outcome' => 'successful',
        'message' => 'Daraja payment status checked successfully.',
        'attempt_number' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payments.reconciliation'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('payments.data.0.events.0.event_type', 'automatic_check')
            ->where('payments.data.0.events.0.outcome', 'successful'));
});

it('exports payment reconciliation records as CSV', function () {
    $admin = paymentReconciliationAdmin();
    $order = Order::factory()->create();
    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'provider_reference' => 'QWE123ABC',
        'status' => Payment::STATUS_SUCCESSFUL,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payments.reconciliation.export'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload();
});
