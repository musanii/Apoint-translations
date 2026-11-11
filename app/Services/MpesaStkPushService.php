<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class MpesaStkPushService
{
    public function __construct(
        private readonly MpesaAccessTokenService $tokenService
    ) {}

    public function initiate(Payment $payment, string $phoneNumber): Payment
    {
        if ($payment->provider !== 'mpesa') {
            throw new InvalidArgumentException(
                'This payment does not use M-Pesa.'
            );
        }
        if ($payment->currency()->value('code') !== 'KES') {
            throw new InvalidArgumentException(
                'M-Pesa payments must be in Kenyan shillings (KES).'
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
        if ($payment->provider_checkout_request_id !== null) {
            throw new InvalidArgumentException('This payment has an STK Push request.');
        }

        if (! preg_match('/^254[17]\d{8}$/', $phoneNumber)) {
            throw new InvalidArgumentException(
                'The phone number must use the format 2547XXXXXXXX or 2541XXXXXXXX.'
            );
        }

        $baseUrl = config('services.mpesa.sandbox')
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';

        $timestamp = now()->format('YmdHis');

        $shortcode = config('services.mpesa.shortcode');
        $passkey = config('services.mpesa.passkey');

        $password = base64_encode(
            $shortcode . $passkey . $timestamp
        );

        $response = Http::withToken(
            $this->tokenService->getAccessToken()
        )
            ->acceptJson()
            ->post(
                $baseUrl . '/mpesa/stkpush/v1/processrequest',
                [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int) $payment->amount,
                    'PartyA' => $phoneNumber,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phoneNumber,
                    'CallBackURL' => config('services.mpesa.callback_url'),
                    'AccountReference' => $payment->reference,
                    'TransactionDesc' => 'Translation services payment',
                ]
            );

        $response->throw();

        $checkoutRequestId = $response->json('CheckoutRequestID');
        $merchantRequestId = $response->json('MerchantRequestID');
        $responseCode = $response->json('ResponseCode');

        if (
            $responseCode !== '0' ||
            ! is_string($checkoutRequestId) ||
            ! is_string($merchantRequestId)
        ) {
            throw new RuntimeException(
                'Daraja did not accept the STK Push request.'
            );
        }

        return DB::transaction(function () use (
            $payment,
            $checkoutRequestId,
            $merchantRequestId
        ) {
            $lockedPayment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if (
                $lockedPayment->status !== Payment::STATUS_PENDING ||
                $lockedPayment->provider_checkout_request_id !== null
            ) {
                throw new InvalidArgumentException(
                    'This payment already has an STK Push request or is no longer pending.'
                );
            }

            $lockedPayment->update([
                'provider_checkout_request_id' => $checkoutRequestId,
                'provider_merchant_request_id' => $merchantRequestId,
                'next_reconciliation_at'=> now()->addMinute(),
            ]);

            return $lockedPayment->refresh();
        });
    }
}
