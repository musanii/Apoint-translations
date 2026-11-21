
<?php

use App\Models\Payment;
use App\Services\PayHeroApiClient;
use App\Services\PayHeroTransactionStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('verifies a late payment using the PayHero transaction reference', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldReceive('getTransactionStatus')
        ->once()
        ->with('PH-TEST-123')
        ->andReturn([
            'success' => true,
            'status' => 'SUCCESS',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
            'amount' => $payment->amount,
        ]);

    app()->instance(PayHeroApiClient::class, $apiClient);

    $result = app(PayHeroTransactionStatusService::class)
        ->verifyLateSuccess($payment);

    expect($result['success'])->toBeTrue()
        ->and($result['status'])->toBe('SUCCESS')
        ->and($result['reference'])->toBe('PH-TEST-123');
});



it('rejects a transaction that PayHero has not confirmed as successful', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldReceive('getTransactionStatus')
        ->once()
        ->with('PH-TEST-123')
        ->andReturn([
            'success' => true,
            'status' => 'QUEUED',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
        ]);

    app()->instance(PayHeroApiClient::class, $apiClient);

    expect(
        fn () => app(PayHeroTransactionStatusService::class)
            ->verifyLateSuccess($payment)
    )->toThrow(
        InvalidArgumentException::class,
        'PayHero has not independently confirmed a successful payment.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED);
});



it('rejects a successful response with a different transaction reference', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldReceive('getTransactionStatus')
        ->once()
        ->with('PH-TEST-123')
        ->andReturn([
            'success' => true,
            'status' => 'SUCCESS',
            'reference' => 'PH-DIFFERENT-456',
            'CheckoutRequestID' => 'checkout-123',
        ]);

    app()->instance(PayHeroApiClient::class, $apiClient);

    expect(
        fn () => app(PayHeroTransactionStatusService::class)
            ->verifyLateSuccess($payment)
    )->toThrow(
        InvalidArgumentException::class,
        'The PayHero transaction reference does not match the payment.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED);
});



it('rejects a successful response with a different checkout request ID', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldReceive('getTransactionStatus')
        ->once()
        ->with('PH-TEST-123')
        ->andReturn([
            'success' => true,
            'status' => 'SUCCESS',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-different',
        ]);

    app()->instance(PayHeroApiClient::class, $apiClient);

    expect(
        fn () => app(PayHeroTransactionStatusService::class)
            ->verifyLateSuccess($payment)
    )->toThrow(
        InvalidArgumentException::class,
        'The PayHero checkout request ID does not match the payment.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED);
});



it('rejects a successful response with a different transaction amount', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'amount' => 100,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldReceive('getTransactionStatus')
        ->once()
        ->with('PH-TEST-123')
        ->andReturn([
            'success' => true,
            'status' => 'SUCCESS',
            'reference' => 'PH-TEST-123',
            'CheckoutRequestID' => 'checkout-123',
            'amount' => 90,
        ]);

    app()->instance(PayHeroApiClient::class, $apiClient);

    expect(
        fn () => app(PayHeroTransactionStatusService::class)
            ->verifyLateSuccess($payment)
    )->toThrow(InvalidArgumentException::class);

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED);
});


it('rejects payments that do not belong to PayHero', function () {
    $payment = Payment::factory()->create([
        'provider' => 'daraja',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_transaction_reference' => null,
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldNotReceive('getTransactionStatus');

    app()->instance(PayHeroApiClient::class, $apiClient);

    expect(
        fn () => app(PayHeroTransactionStatusService::class)
            ->verifyLateSuccess($payment)
    )->toThrow(
        InvalidArgumentException::class,
        'Only PayHero M-Pesa payments can be verified.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED);
});

it('rejects payments that are not in a failed state', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_PENDING,
        'provider_transaction_reference' => 'PH-TEST-123',
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldNotReceive('getTransactionStatus');

    app()->instance(PayHeroApiClient::class, $apiClient);

    expect(
        fn () => app(PayHeroTransactionStatusService::class)
            ->verifyLateSuccess($payment)
    )->toThrow(
        InvalidArgumentException::class,
        'Only failed payments can undergo late-success verification.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_PENDING);
});

it('rejects a failed payment without a PayHero transaction reference', function () {
    $payment = Payment::factory()->create([
        'provider' => 'payhero',
        'payment_method' => 'mpesa',
        'status' => Payment::STATUS_FAILED,
        'provider_transaction_reference' => null,
        'provider_checkout_request_id' => 'checkout-123',
    ]);

    $apiClient = Mockery::mock(PayHeroApiClient::class);

    $apiClient->shouldNotReceive('getTransactionStatus');

    app()->instance(PayHeroApiClient::class, $apiClient);

    expect(
        fn () => app(PayHeroTransactionStatusService::class)
            ->verifyLateSuccess($payment)
    )->toThrow(
        InvalidArgumentException::class,
        'The payment is missing its PayHero transaction reference.'
    );

    expect($payment->fresh()->status)
        ->toBe(Payment::STATUS_FAILED);
});