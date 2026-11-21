<?php

use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaCallbackService;
use App\Services\MpesaStkPushQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

it('marks a payment successful when Daraja confirms the STK payment', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'ws_CO_123456789',
        'provider_merchant_request_id' => 'merchant-123',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-123',
                'CheckoutRequestID' => 'ws_CO_123456789',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGH123ABC'],
                        ['Name' => 'TransactionDate', 'Value' => 20261110103000],
                        ['Name' => 'PhoneNumber', 'Value' => 254712345678],
                    ],
                ],
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)->handle($callback);

    expect($result->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)->toBe('QGH123ABC')
        ->and($result->fresh()->order->payment_status)->toBe(Order::PAYMENT_STATUS_PAID);
});

it('marks a payment failed when Daraja reports an unsuccessful STK payment', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'provider_checkout_request_id' => 'ws_CO_FAILED_123',
        'provider_merchant_request_id' => 'merchant-failed',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-failed',
                'CheckoutRequestID' => 'ws_CO_FAILED_123',
                'ResultCode' => 1032,
                'ResultDesc' => 'Request cancelled by user.',
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)->handle($callback);

    expect($result->status)->toBe(Payment::STATUS_FAILED)
        ->and($result->failure_reason)->toBe('Request cancelled by user.')
        ->and($result->fresh()->order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('handles a duplicate successful callback without processing the payment twice', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'ws_CO_DUPLICATE_123',
        'provider_merchant_request_id' => 'merchant-123',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-123',
                'CheckoutRequestID' => 'ws_CO_DUPLICATE_123',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGH123ABC'],
                    ],
                ],
            ],
        ],
    ];

    $service = app(MpesaCallbackService::class);

    $firstResult = $service->handle($callback);
    $secondResult = $service->handle($callback);

    expect($firstResult->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($secondResult->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($secondResult->provider_reference)->toBe('QGH123ABC')
        ->and(Payment::query()->where('id', $payment->id)->count())->toBe(1);
});

it('rejects a successful callback when the amount does not match the payment', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'ws_CO_WRONG_AMOUNT',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'CheckoutRequestID' => 'ws_CO_WRONG_AMOUNT',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 50],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGH123WRONG'],
                    ],
                ],
            ],
        ],
    ];

    expect(fn () => app(MpesaCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING)
        ->and($payment->fresh()->provider_reference)->toBeNull();
});

it('returns null when the callback references an unknown checkout request', function () {
    $callback = [
        'Body' => [
            'stkCallback' => [
                'CheckoutRequestID' => 'ws_CO_UNKNOWN_123',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGHUNKNOWN'],
                    ],
                ],
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)->handle($callback);

    expect($result)->toBeNull();
});

it('rejects a callback with a missing STK callback body', function () {
    expect(fn () => app(MpesaCallbackService::class)->handle([
        'Body' => [],
    ]))->toThrow(InvalidArgumentException::class);
});

it('rejects a successful callback without an M-Pesa receipt number', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'ws_CO_NO_RECEIPT',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'CheckoutRequestID' => 'ws_CO_NO_RECEIPT',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                    ],
                ],
            ],
        ],
    ];

    expect(fn () => app(MpesaCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING);
});

it('rejects a callback with a mismatched merchant request ID', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'ws_CO_MERCHANT_CHECK',
        'provider_merchant_request_id' => 'merchant-expected',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-unexpected',
                'CheckoutRequestID' => 'ws_CO_MERCHANT_CHECK',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGH123ABC'],
                    ],
                ],
            ],
        ],
    ];

    expect(fn () => app(MpesaCallbackService::class)->handle($callback))
        ->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)->toBe(Payment::STATUS_PENDING);
});

it('attaches the receipt when a successful callback arrives after reconciliation', function () {
    $order = Order::factory()->create([
        'total' => 100,
        'payment_status' => Order::PAYMENT_STATUS_PAID,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_SUCCESSFUL,
        'provider_checkout_request_id' => 'ws_CO_123456',
        'provider_merchant_request_id' => 'merchant-request-123',
        'provider_reference' => null,
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-request-123',
                'CheckoutRequestID' => 'ws_CO_123456',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        [
                            'Name' => 'Amount',
                            'Value' => 100,
                        ],
                        [
                            'Name' => 'MpesaReceiptNumber',
                            'Value' => 'TESTRCPT002',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)
        ->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBe('TESTRCPT002')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID);
});

it('recovers a failed payment when Daraja independently confirms late success', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
        'total' => 100,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_FAILED,
        'failure_reason' => 'Payment timed out.',
        'provider_reference' => null,
        'provider_checkout_request_id' => 'ws_CO_LATE_123',
        'provider_merchant_request_id' => 'merchant-late-123',
    ]);

    $queryService = Mockery::mock(MpesaStkPushQueryService::class);

    $queryService->shouldReceive('verifyLateSuccess')
        ->once()
        ->with(Mockery::on(
            fn ($candidate) => $candidate->is($payment)
        ))
        ->andReturn([
            'CheckoutRequestID' => 'ws_CO_LATE_123',
            'MerchantRequestID' => 'merchant-late-123',
            'ResultCode' => 0,
        ]);

    app()->instance(MpesaStkPushQueryService::class, $queryService);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-late-123',
                'CheckoutRequestID' => 'ws_CO_LATE_123',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'LATE123ABC'],
                    ],
                ],
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($result->provider_reference)
        ->toBe('LATE123ABC')
        ->and($result->failure_reason)
        ->toBeNull()
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID)
        ->and($order->fresh()->status)
        ->toBe(Order::STATUS_CONFIRMED);
});

it('keeps a failed payment unchanged when Daraja does not confirm late success', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
        'total' => 100,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_FAILED,
        'failure_reason' => 'Payment timed out.',
        'provider_reference' => null,
        'provider_checkout_request_id' => 'ws_CO_LATE_FAIL',
        'provider_merchant_request_id' => 'merchant-late-fail',
    ]);

    $queryService = Mockery::mock(MpesaStkPushQueryService::class);

    $queryService->shouldReceive('verifyLateSuccess')
        ->once()
        ->andReturn([
            'CheckoutRequestID' => 'ws_CO_LATE_FAIL',
            'MerchantRequestID' => 'merchant-late-fail',
            'ResultCode' => 1032,
        ]);

    app()->instance(MpesaStkPushQueryService::class, $queryService);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-late-fail',
                'CheckoutRequestID' => 'ws_CO_LATE_FAIL',
                'ResultCode' => 0,
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'LATEFAIL123'],
                    ],
                ],
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($result->provider_reference)
        ->toBeNull()
        ->and($result->failure_reason)
        ->toBe('Payment timed out.')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('does not recover a failed payment when Daraja returns a mismatched checkout request ID', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
        'total' => 100,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_FAILED,
        'failure_reason' => 'Payment timed out.',
        'provider_reference' => null,
        'provider_checkout_request_id' => 'ws_CO_EXPECTED',
        'provider_merchant_request_id' => 'merchant-expected',
    ]);

    $queryService = Mockery::mock(MpesaStkPushQueryService::class);

    $queryService->shouldReceive('verifyLateSuccess')
        ->once()
        ->andReturn([
            'CheckoutRequestID' => 'ws_CO_DIFFERENT',
            'MerchantRequestID' => 'merchant-expected',
            'ResultCode' => 0,
        ]);

    app()->instance(MpesaStkPushQueryService::class, $queryService);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-expected',
                'CheckoutRequestID' => 'ws_CO_EXPECTED',
                'ResultCode' => 0,
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'MISMATCH123'],
                    ],
                ],
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($result->provider_reference)
        ->toBeNull()
        ->and($result->failure_reason)
        ->toBe('Payment timed out.')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('does not recover a failed payment when Daraja returns a mismatched merchant request ID', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
        'total' => 100,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_FAILED,
        'failure_reason' => 'Payment timed out.',
        'provider_reference' => null,
        'provider_checkout_request_id' => 'ws_CO_MERCHANT_EXPECTED',
        'provider_merchant_request_id' => 'merchant-expected',
    ]);

    $queryService = Mockery::mock(MpesaStkPushQueryService::class);

    $queryService->shouldReceive('verifyLateSuccess')
        ->once()
        ->andReturn([
            'CheckoutRequestID' => 'ws_CO_MERCHANT_EXPECTED',
            'MerchantRequestID' => 'merchant-different',
            'ResultCode' => 0,
        ]);

    app()->instance(MpesaStkPushQueryService::class, $queryService);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-expected',
                'CheckoutRequestID' => 'ws_CO_MERCHANT_EXPECTED',
                'ResultCode' => 0,
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'MERCHANTMISMATCH123'],
                    ],
                ],
            ],
        ],
    ];

    $result = app(MpesaCallbackService::class)->handle($callback);

    expect($result->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($result->provider_reference)
        ->toBeNull()
        ->and($result->failure_reason)
        ->toBe('Payment timed out.')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});

it('does not recover a failed payment when Daraja verification throws an exception', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_PENDING_PAYMENT,
        'payment_status' => Order::PAYMENT_STATUS_FAILED,
        'total' => 100,
    ]);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'status' => Payment::STATUS_FAILED,
        'failure_reason' => 'Payment timed out.',
        'provider_reference' => null,
        'provider_checkout_request_id' => 'ws_CO_DARAJA_ERROR',
        'provider_merchant_request_id' => 'merchant-expected',
    ]);

    $queryService = Mockery::mock(MpesaStkPushQueryService::class);

    $queryService->shouldReceive('verifyLateSuccess')
        ->once()
        ->andThrow(new RuntimeException('Daraja unavailable.'));

    app()->instance(MpesaStkPushQueryService::class, $queryService);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-expected',
                'CheckoutRequestID' => 'ws_CO_DARAJA_ERROR',
                'ResultCode' => 0,
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'DARAJAERROR123'],
                    ],
                ],
            ],
        ],
    ];

    expect(fn () => app(MpesaCallbackService::class)->handle($callback))
        ->toThrow(RuntimeException::class, 'Daraja unavailable.');

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED)
        ->and($payment->fresh()->provider_reference)
        ->toBeNull()
        ->and($payment->fresh()->failure_reason)
        ->toBe('Payment timed out.')
        ->and($order->fresh()->payment_status)
        ->toBe(Order::PAYMENT_STATUS_FAILED);
});
