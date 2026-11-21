<?php

namespace App\Services;

use App\Models\Payment;
use InvalidArgumentException;

class PayHeroTransactionStatusService
{
    public function __construct(
        private readonly PayHeroApiClient $apiClient
    ) {}

    public function verifyLateSuccess(Payment $payment): array
    {
        if (
            $payment->provider !== 'payhero' ||
            $payment->payment_method !== 'mpesa'
        ) {
            throw new InvalidArgumentException(
                'Only PayHero M-Pesa payments can be verified.'
            );
        }

        if ($payment->status !== Payment::STATUS_FAILED) {
            throw new InvalidArgumentException(
                'Only failed payments can undergo late-success verification.'
            );
        }

        $reference = $payment->provider_transaction_reference;

        if (! is_string($reference) || $reference === '') {
            throw new InvalidArgumentException(
                'The payment is missing its PayHero transaction reference.'
            );
        }

        $response = $this->apiClient->getTransactionStatus($reference);

        if (
            ($response['success'] ?? false) !== true ||
            strtoupper((string) ($response['status'] ?? '')) !== 'SUCCESS'
        ) {
            throw new InvalidArgumentException(
                'PayHero has not independently confirmed a successful payment.'
            );
        }

        if (($response['reference'] ?? null) !== $reference) {
            throw new InvalidArgumentException(
                'The PayHero transaction reference does not match the payment.'
            );
        }

        if (
            ($response['CheckoutRequestID'] ?? null) !==
            $payment->provider_checkout_request_id
        ) {
            throw new InvalidArgumentException(
                'The PayHero checkout request ID does not match the payment.'
            );
        }

        $amount = $response['amount'] ?? null;

        if (
            ! is_numeric($amount) ||
            (float) $amount !== (float) $payment->amount
        ) {
            throw new InvalidArgumentException(
                'The PayHero transaction amount does not match the payment amount.'
            );
        }

        return $response;
    }
}
