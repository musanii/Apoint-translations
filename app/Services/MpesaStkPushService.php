<?php

namespace App\\Services;

use App\\Models\\Payment;
use Illuminate\\Http\\Client\\RequestException;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Http;
use InvalidArgumentException;
use RuntimeException;

class MpesaStkPushService
{
    public function __construct(
        private readonly MpesaAccessTokenService $tokenService
    ) {}

    public function initiate(Payment $payment, string $phoneNumber): Payment
    {
        if ($payment->provider !== 'daraja' || $payment->payment_method !== 'mpesa') {
            throw new InvalidArgumentException('This payment does not use Daraja M-Pesa.');
        }

        if ($payment->currency()->value('code') !== 'KES') {
            throw new InvalidArgumentException('M-Pesa payments must be in Kenyan shillings (KES).');
        }

        if (fmod((float) $payment->amount, 1.0) !== 0.0) {
            throw new InvalidArgumentException('M-Pesa payments must use whole-number amounts.');
        }

        if (! preg_match('/^254[17]\\d{8}$/', $phoneNumber)) {
            throw new InvalidArgumentException('The phone number must use the format 2547XXXXXXXX or 2541XXXXXXXX.');
        }

        // Claim the payment before contacting Safaricom. This prevents concurrent
        // requests from creating multiple STK Push prompts for the same attempt.
        $claimedPayment = DB::transaction(function () use ($payment) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (
                $lockedPayment->status !== Payment::STATUS_PENDING ||
                $lockedPayment->provider_checkout_request_id !== null ||
                $lockedPayment->provider_initiation_started_at !== null
            ) {
                throw new InvalidArgumentException('This payment already has an STK Push request or initiation in progress.');
            }

            $lockedPayment->update(['provider_initiation_started_at' => now()]);

            return $lockedPayment->refresh();
        });

        $baseUrl = config('services.mpesa.sandbox')
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';

        $timestamp = now()->format('YmdHis');
        $shortcode = config('services.mpesa.shortcode');
        $passkey = config('services.mpesa.passkey');
        $password = base64_encode($shortcode.$passkey.$timestamp);

        try {
            $accessToken = $this->tokenService->getAccessToken();

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post($baseUrl.'/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int) $claimedPayment->amount,
                    'PartyA' => $phoneNumber,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phoneNumber,
                    'CallBackURL' => config('services.mpesa.callback_url'),
                    'AccountReference' => $claimedPayment->reference,
                    'TransactionDesc' => 'Translation services payment',
                ]);

            $response->throw();
        } catch (RequestException $exception) {
            // A definite 4xx rejection is safe to retry. A server error is
            // ambiguous, so retain the claim until the payment is reconciled.
            if ($exception->response->clientError()) {
                $this->releaseInitiationClaim($claimedPayment);
            }

            throw $exception;
        }

        $checkoutRequestId = $response->json('CheckoutRequestID');
        $merchantRequestId = $response->json('MerchantRequestID');

        if (
            $response->json('ResponseCode') !== '0' ||
            ! is_string($checkoutRequestId) ||
            $checkoutRequestId === '' ||
            ! is_string($merchantRequestId) ||
            $merchantRequestId === ''
        ) {
            $this->releaseInitiationClaim($claimedPayment);

            throw new RuntimeException('Daraja did not accept the STK Push request.');
        }

        return DB::transaction(function () use ($claimedPayment, $checkoutRequestId, $merchantRequestId) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($claimedPayment->id);

            if (
                $lockedPayment->status !== Payment::STATUS_PENDING ||
                $lockedPayment->provider_checkout_request_id !== null ||
                $lockedPayment->provider_initiation_started_at === null
            ) {
                throw new InvalidArgumentException('The payment initiation claim is no longer valid.');
            }

            $lockedPayment->update([
                'provider_checkout_request_id' => $checkoutRequestId,
                'provider_merchant_request_id' => $merchantRequestId,
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
            ->update(['provider_initiation_started_at' => null]);
    }
}
