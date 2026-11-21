<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use RuntimeException;


class PayHeroStkPushService
{
    public function __construct(
        private readonly PayHeroApiClient $apiClient
    ) {}


    public function initiate(Payment $payment, string $phoneNumber): Payment
    {
        if (
            $payment->provider !== 'payhero' ||
            $payment->payment_method !== 'mpesa'
        ) {
            throw new InvalidArgumentException(
                'This payment does not use PayHero M-Pesa.'
            );
        }

        if ($payment->currency()->value('code') !== 'KES') {
            throw new InvalidArgumentException(
                'PayHero M-Pesa payments must be in Kenyan shillings (KES).'
            );
        }

        if (fmod((float) $payment->amount, 1.0) !== 0.0) {
            throw new InvalidArgumentException(
                'M-Pesa payments must use whole-number amounts.'
            );
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            throw new InvalidArgumentException(
                'Only pending payments can initiate an STK Push.'
            );
        }

        if (! preg_match('/^254[17]\d{8}$/', $phoneNumber)) {
            throw new InvalidArgumentException(
                'The phone number must use the format 2547XXXXXXXX or 2541XXXXXXXX.'
            );
        }

        $channelId = $payment->provider_channel_id;

        if ($channelId === null || ! ctype_digit($channelId)) {
            throw new InvalidArgumentException(
                'A valid PayHero channel ID is required.'
            );
        }

        // Claim the payment before contacting PayHero.
        $claimedPayment = DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (
                $lockedPayment->status !== Payment::STATUS_PENDING ||
                $lockedPayment->provider_checkout_request_id !== null ||
                $lockedPayment->provider_initiation_started_at !== null
            ) {
                throw new InvalidArgumentException(
                    'This payment already has an STK Push request or initiation in progress.'
                );
            }

            $lockedPayment->update([
                'provider_initiation_started_at' => now(),
            ]);

            return $lockedPayment->refresh();
        });

        try {
            $response = $this->apiClient->initiateStkPush([
                'amount' => (int) $claimedPayment->amount,
                'phone_number' => $phoneNumber,
                'channel_id' => (int) $channelId,
                'provider' => 'm-pesa',
                'external_reference' => $claimedPayment->reference,
                'callback_url' => config('services.payhero.callback_url'),
            ]);
        } catch (RequestException $exception) {
            // A definite client-side rejection can release the claim.
            // Server errors remain claimed because their outcome may be ambiguous.
            if ($exception->response->clientError()) {
                $this->releaseInitiationClaim($claimedPayment);
            }

            throw $exception;
        }

        if (($response['success'] ?? false) !== true) {
            $this->releaseInitiationClaim($claimedPayment);

            throw new RuntimeException(
                'PayHero did not accept the STK Push request.'
            );
        }

        $providerReference = $response['reference'] ?? null;
        $checkoutRequestId = $response['CheckoutRequestID'] ?? null;

        if (
            ($response['status'] ?? null) !== 'QUEUED' ||
            ! is_string($providerReference) ||
            $providerReference === '' ||
            ! is_string($checkoutRequestId) ||
            $checkoutRequestId === ''
        ) {
            throw new RuntimeException(
                'PayHero returned an incomplete or unexpected STK Push response.'
            );
        }

        return DB::transaction(function () use (
            $claimedPayment,
            $providerReference,
            $checkoutRequestId
        ) {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($claimedPayment->id);

            if (
                $lockedPayment->status !== Payment::STATUS_PENDING ||
                $lockedPayment->provider_checkout_request_id !== null ||
                $lockedPayment->provider_initiation_started_at === null
            ) {
                throw new InvalidArgumentException(
                    'The payment initiation claim is no longer valid.'
                );
            }

            $lockedPayment->update([
                'provider_transaction_reference' => $providerReference,
                'provider_checkout_request_id' => $checkoutRequestId,
                'provider_initiation_started_at' => null,
                'next_reconciliation_at' => now()->addMinute(),
            ]);

            return $lockedPayment->refresh();
        });
    }

    private function releaseInitiationClaim(Payment $payment): void
    {
        Payment::query()
            ->whereKey($payment->id)
            ->whereNull('provider_checkout_request_id')
            ->update([
                'provider_initiation_started_at' => null,
            ]);
    }
}
