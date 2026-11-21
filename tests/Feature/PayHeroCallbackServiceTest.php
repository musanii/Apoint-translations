<?php

use App\Models\Payment;
use App\Models\Order;
use App\Services\PayHeroCallbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('marks a payment successful when PayHero confirms the M-Pesa payment', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Success',
        ],
    ];

    $result = app(PayHeroCallbackService::class)->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBe('QGH123ABC')
        ->and($result->provider_transaction_reference)
        ->toBe('PH-TEST-123')
        ->and($result->fresh()->order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID);
});


it('does not process a duplicate successful callback twice', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Success',
        ],
    ];

    $service = app(PayHeroCallbackService::class);

    $service->handle($callback);
    $result = $service->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBe('QGH123ABC')
        ->and(Payment::where('provider_checkout_request_id', 'checkout-123')->count())
        ->toBe(1)
        ->and($result->fresh()->order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID);
});



it('does not mark a payment successful when the callback amount does not match', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 90,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Success',
        ],
    ];

    expect(fn () => app(PayHeroCallbackService::class)->handle($callback))
    ->toThrow(
        InvalidArgumentException::class,
        'The callback amount does not match the payment amount.'
    );

$payment->refresh();

expect($payment->status)
    ->toBe(Payment::STATUS_PENDING)
    ->and($payment->provider_reference)
    ->toBeNull()
    ->and($payment->provider_transaction_reference)
    ->toBe('PH-TEST-123');
});



it('rejects a callback with an incorrect external reference', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => 'WRONG-REFERENCE',
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Success',
        ],
    ];

    expect(fn () => app(PayHeroCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    $payment->refresh();

    expect($payment->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($payment->provider_reference)
        ->toBeNull();
});


it('rejects a successful callback without an M-Pesa receipt number', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => '',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Success',
        ],
    ];

    expect(fn () => app(PayHeroCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    $payment->refresh();

    expect($payment->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($payment->provider_reference)
        ->toBeNull();
});


it('ignores callbacks for unknown checkout requests', function () {
    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'unknown-checkout',
            'ExternalReference' => 'APT-O-2026-000001',
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Success',
        ],
    ];

    $result = app(PayHeroCallbackService::class)->handle($callback);

    expect($result)->toBeNull()
        ->and(Payment::count())->toBe(0);
});


it('does not process a callback belonging to a Daraja payment', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Success',
        ],
    ];

    $result = app(PayHeroCallbackService::class)->handle($callback);

    expect($result)->toBeNull()
        ->and($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($payment->fresh()->provider_reference)
        ->toBeNull();
});



it('rejects a callback with contradictory payment status information', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'Status' => 'Failed',
        ],
    ];

    expect(fn () => app(PayHeroCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    $payment->refresh();

    expect($payment->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($payment->provider_reference)
        ->toBeNull();
});



it('marks a payment failed when PayHero confirms an M-Pesa failure', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => null,
            'Phone' => '+254712345678',
            'ResultCode' => 1032,
            'ResultDesc' => 'Request cancelled by user.',
            'Status' => 'Failed',
        ],
    ];

    $result = app(PayHeroCallbackService::class)->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($result->provider_reference)
        ->toBeNull()
        ->and($result->provider_transaction_reference)
        ->toBe('PH-TEST-123')
        ->and($result->fresh()->order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});


it('does not process a duplicate failed callback twice', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => null,
            'Phone' => '+254712345678',
            'ResultCode' => 1032,
            'ResultDesc' => 'Request cancelled by user.',
            'Status' => 'Failed',
        ],
    ];

    $service = app(PayHeroCallbackService::class);

    $firstResult = $service->handle($callback);
    $secondResult = $service->handle($callback);

    expect($firstResult->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($secondResult->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($secondResult->failure_reason)
        ->toBe('Request cancelled by user.')
        ->and($secondResult->provider_reference)
        ->toBeNull()
        ->and($secondResult->fresh()->order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});


it('rejects a failed result code paired with a success status', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'MerchantRequestID' => 'merchant-123',
            'MpesaReceiptNumber' => 'QGH123ABC',
            'Phone' => '+254712345678',
            'ResultCode' => 1032,
            'ResultDesc' => 'Request cancelled by user.',
            'Status' => 'Success',
        ],
    ];

    expect(fn () => app(PayHeroCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    $payment->refresh();

    expect($payment->status)
        ->toBe(Payment::STATUS_PENDING)
        ->and($payment->provider_reference)
        ->toBeNull();
});


it('rejects a callback without a response object', function () {
    expect(fn () => app(PayHeroCallbackService::class)->handle([
        'status' => true,
    ]))->toThrow(
        InvalidArgumentException::class,
        'Invalid PayHero callback payload.'
    );
});


it('rejects a callback without a checkout request ID', function () {
    expect(fn () => app(PayHeroCallbackService::class)->handle([
        'status' => true,
        'response' => [
            'Amount' => 100,
            'ExternalReference' => 'APT-O-2026-000001',
            'ResultCode' => 0,
            'Status' => 'Success',
        ],
    ]))->toThrow(
        InvalidArgumentException::class,
        'The callback is missing its checkout request ID.'
    );
});


it('rejects a callback with a non-numeric result code', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'ResultCode' => 'unknown',
            'Status' => 'Success',
            'MpesaReceiptNumber' => 'QGH123ABC',
        ],
    ];

    expect(fn () => app(PayHeroCallbackService::class)->handle($callback))
        ->toThrow(
            InvalidArgumentException::class,
            'The callback has an invalid result code.'
        );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING);
});



it('rejects an unknown callback payment status', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'Amount' => 100,
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'ResultCode' => 0,
            'Status' => 'Pending',
            'MpesaReceiptNumber' => 'QGH123ABC',
        ],
    ];

    expect(fn () => app(PayHeroCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING);
});



it('recovers a failed payment after PayHero independently confirms late success', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => 'failed',
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    Http::fake([
        'backend.payhero.co.ke/api/v2/transaction-status*' => Http::response([
            'success' => true,
            'status' => 'SUCCESS',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
            'amount' => 100,
            'provider_reference' => 'RCP123456',
        ], 200),
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'ResultCode' => 0,
            'Status' => 'Success',
            'Amount' => 100,
            'MpesaReceiptNumber' => 'RCP123456',
        ],
    ];

    $result = app(PayHeroCallbackService::class)->handle($callback);

    expect($result->fresh()->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->fresh()->provider_reference)
        ->toBe('RCP123456')
        ->and($result->fresh()->provider_transaction_reference)
        ->toBe('PH-TEST-123')
        ->and($result->fresh()->failure_reason)
        ->toBeNull()
        ->and($order->fresh()->payment_status)
        ->toBe('paid');

   Http::assertSent(function ($request) {
    return str_contains(
        $request->url(),
        '/api/v2/transaction-status'
    ) && str_contains(
        $request->url(),
        'reference=PH-TEST-123'
    );
});
});


it('does not recover a failed payment when PayHero has not confirmed late success', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => 'failed',
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
        'failure_reason' => 'Payment timed out.',
    ]);

    Http::fake([
        'backend.payhero.co.ke/api/v2/transaction-status*' => Http::response([
            'success' => true,
            'status' => 'QUEUED',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
            'amount' => 100,
        ], 200),
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'ResultCode' => 0,
            'Status' => 'Success',
            'Amount' => 100,
            'MpesaReceiptNumber' => 'RCP123456',
        ],
    ];

    expect(
        fn () => app(PayHeroCallbackService::class)->handle($callback)
    )->toThrow(
        InvalidArgumentException::class,
        'PayHero has not independently confirmed a successful payment.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($payment->fresh()->failure_reason)
        ->toBe('Payment timed out.')
        ->and($payment->fresh()->provider_reference)
        ->toBeNull()
        ->and($order->fresh()->payment_status)
        ->toBe('failed');
});


it('rejects a late success when the verified receipt differs from the callback receipt', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => 'failed',
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
        'failure_reason' => 'Payment timed out.',
    ]);

    Http::fake([
        'backend.payhero.co.ke/api/v2/transaction-status*' => Http::response([
            'success' => true,
            'status' => 'SUCCESS',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
            'amount' => 100,
            'provider_reference' => 'RCP-VERIFIED-999',
        ], 200),
    ]);

    $callback = [
        'status' => true,
        'response' => [
            'CheckoutRequestID' => 'checkout-123',
            'ExternalReference' => $payment->reference,
            'ResultCode' => 0,
            'Status' => 'Success',
            'Amount' => 100,
            'MpesaReceiptNumber' => 'RCP-CALLBACK-123',
        ],
    ];

    expect(
        fn () => app(PayHeroCallbackService::class)->handle($callback)
    )->toThrow(
        InvalidArgumentException::class,
        'The PayHero verified receipt does not match the callback receipt.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($payment->fresh()->provider_reference)
        ->toBeNull()
        ->and($payment->fresh()->failure_reason)
        ->toBe('Payment timed out.')
        ->and($order->fresh()->payment_status)
        ->toBe('failed');
});










