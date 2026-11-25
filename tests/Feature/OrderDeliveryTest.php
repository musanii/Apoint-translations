<?php

use App\Mail\ApointNotificationMail;
use App\Models\Order;
use App\Models\OrderDeliveryDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Artisan::call('migrate:fresh', ['--force' => true]);
    Storage::fake('local');
});

function makeDeliveryAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

it('uploads final documents, completes a digital order and notifies the customer', function (): void {
    Mail::fake();

    $admin = makeDeliveryAdmin();
    $order = Order::factory()->create([
        'status' => Order::STATUS_IN_PROGRESS,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'delivery_method' => 'digital',
    ]);

    $response = $this->actingAs($admin)->post(route('admin.orders.delivery-documents.store', $order), [
        'documents' => [
            UploadedFile::fake()->create('translated-document.pdf', 120, 'application/pdf'),
        ],
    ]);

    $response->assertRedirect()->assertSessionHas('success');

    $document = OrderDeliveryDocument::query()->where('order_id', $order->id)->firstOrFail();

    expect($order->fresh()->status)->toBe(Order::STATUS_COMPLETED)
        ->and($document->original_name)->toBe('translated-document.pdf')
        ->and(Storage::disk('local')->exists($document->path))->toBeTrue()
        ->and($order->user->notifications()->count())->toBe(1);

    Mail::assertSent(ApointNotificationMail::class, fn (ApointNotificationMail $mail): bool => (
        $mail->title === 'Your documents are ready'
        && $mail->actionUrl === route('orders.show', $order)
    ));
});

it('allows only the owning customer to view and download delivered documents', function (): void {
    $admin = makeDeliveryAdmin();
    $order = Order::factory()->create([
        'status' => Order::STATUS_IN_PROGRESS,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'delivery_method' => 'digital',
    ]);

    $this->actingAs($admin)->post(route('admin.orders.delivery-documents.store', $order), [
        'documents' => [UploadedFile::fake()->create('final.pdf', 20, 'application/pdf')],
    ])->assertRedirect();

    $document = $order->deliveryDocuments()->firstOrFail();
    $customer = $order->user;

    $this->actingAs($customer)
        ->get(route('orders.documents.preview', [$order, $document]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    $this->actingAs($customer)
        ->get(route('orders.documents.download', [$order, $document]))
        ->assertOk();

    $this->actingAs(User::factory()->create())
        ->get(route('orders.documents.download', [$order, $document]))
        ->assertNotFound();
});

it('does not allow a digital order to be completed without final documents', function (): void {
    $order = Order::factory()->create([
        'status' => Order::STATUS_IN_PROGRESS,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
        'delivery_method' => 'digital',
    ]);

    expect(fn () => app(\App\Services\OrderStatusService::class)->complete($order))
        ->toThrow(\InvalidArgumentException::class);

    expect($order->fresh()->status)->toBe(Order::STATUS_IN_PROGRESS);
});
