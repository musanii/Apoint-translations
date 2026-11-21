<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('accepts a successful Daraja callback and returns an acknowledgement', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'ws_CO_CONTROLLER_123',
        'provider_merchant_request_id' => 'merchant-controller-123',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-controller-123',
                'CheckoutRequestID' => 'ws_CO_CONTROLLER_123',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGHCONTROLLER'],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->postJson('/mpesa/callback', $callback);

    $response->assertOk()
        ->assertJsonPath('ResultCode', 0)
        ->assertJsonPath('ResultDesc', 'Accepted');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->fresh()->order->payment_status)
        ->toBe(Order::PAYMENT_STATUS_PAID);
});

it('acknowledges a failed payment callback and records the failure', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'provider_checkout_request_id' => 'ws_CO_CONTROLLER_FAILED',
        'provider_merchant_request_id' => 'merchant-controller-failed',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-controller-failed',
                'CheckoutRequestID' => 'ws_CO_CONTROLLER_FAILED',
                'ResultCode' => 1032,
                'ResultDesc' => 'Request cancelled by user.',
            ],
        ],
    ];

    $response = $this->postJson('/mpesa/callback', $callback);

    $response->assertOk()
        ->assertJsonPath('ResultCode', 0)
        ->assertJsonPath('ResultDesc', 'Accepted');

    expect($payment->fresh()->status)->toBe(Payment::STATUS_FAILED)
        ->and($payment->fresh()->failure_reason)
        ->toBe('Request cancelled by user.');
});

it('acknowledges duplicate callbacks without processing the payment twice', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'amount' => 100,
        'provider_checkout_request_id' => 'ws_CO_CONTROLLER_DUPLICATE',
        'provider_merchant_request_id' => 'merchant-controller-duplicate',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-controller-duplicate',
                'CheckoutRequestID' => 'ws_CO_CONTROLLER_DUPLICATE',
                'ResultCode' => 0,
                'ResultDesc' => 'The service request is processed successfully.',
                'CallbackMetadata' => [
                    'Item' => [
                        ['Name' => 'Amount', 'Value' => 100],
                        ['Name' => 'MpesaReceiptNumber', 'Value' => 'QGHDUPLICATE'],
                    ],
                ],
            ],
        ],
    ];

    $firstResponse = $this->postJson('/mpesa/callback', $callback);
    $secondResponse = $this->postJson('/mpesa/callback', $callback);

    $firstResponse->assertOk();
    $secondResponse->assertOk();

    expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESSFUL)
        ->and($payment->fresh()->provider_reference)->toBe('QGHDUPLICATE');
});

it('acknowledges callbacks for unknown checkout request IDs', function () {
    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-unknown',
                'CheckoutRequestID' => 'ws_CO_UNKNOWN_CONTROLLER',
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

    $response = $this->postJson('/mpesa/callback', $callback);

    $response->assertOk()
        ->assertJsonPath('ResultCode', 0)
        ->assertJsonPath('ResultDesc', 'Accepted');

    expect(Payment::query()->count())->toBe(0);
});

it('does not process a PayHero payment through the Daraja callback endpoint', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'provider_checkout_request_id' => 'ws_CO_PAYHERO_123',
        'provider_merchant_request_id' => 'merchant-payhero-123',
    ]);

    $callback = [
        'Body' => [
            'stkCallback' => [
                'MerchantRequestID' => 'merchant-payhero-123',
                'CheckoutRequestID' => 'ws_CO_PAYHERO_123',
                'ResultCode' => 1032,
                'ResultDesc' => 'Request cancelled by user.',
            ],
        ],
    ];

    $response = $this->postJson('/mpesa/callback', $callback);

    $response->assertOk()
        ->assertJsonPath('ResultCode', 0);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING);
});
