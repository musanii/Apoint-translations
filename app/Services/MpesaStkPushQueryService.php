<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class MpesaStkPushQueryService
{
    public function __construct(
        private readonly MpesaAccessTokenService $tokenService
    ) {}

    public function query(Payment $payment): array
    {
        if (
            $payment->provider !== 'daraja' ||
            $payment->payment_method !== 'mpesa'
        ) {
            throw new InvalidArgumentException(
                'This payment does not use Daraja M-Pesa.'
            );
        }

        if ($payment->status !== Payment::STATUS_PENDING) {
            throw new InvalidArgumentException(
                'Only pending payments can be queried.'
            );
        }

        if (! $payment->provider_checkout_request_id) {
            throw new InvalidArgumentException(
                'This payment does not have an STK Push checkout request ID.'
            );
        }

        return $this->sendQuery($payment);
    }

    public function verifyLateSuccess(Payment $payment): array
    {
        if (
            $payment->provider !== 'daraja' ||
            $payment->payment_method !== 'mpesa'
        ) {
            throw new InvalidArgumentException(
                'This payment does not use Daraja M-Pesa.'
            );
        }

        if ($payment->status !== Payment::STATUS_FAILED) {
            throw new InvalidArgumentException(
                'Only failed payments can undergo late-success verification.'
            );
        }

        return $this->sendQuery($payment);
    }

    private function sendQuery(Payment $payment): array
    {
        if (! $payment->provider_checkout_request_id) {
            throw new InvalidArgumentException(
                'This payment does not have an STK Push checkout request ID.'
            );
        }

        $baseUrl = config('services.mpesa.sandbox')
            ? 'https://sandbox.safaricom.co.ke'
            : 'https://api.safaricom.co.ke';

        $timestamp = now()->format('YmdHis');

        $shortcode = config('services.mpesa.shortcode');
        $passkey = config('services.mpesa.passkey');

        $password = base64_encode(
            $shortcode.$passkey.$timestamp
        );

        $response = Http::withToken(
            $this->tokenService->getAccessToken()
        )
            ->acceptJson()
            ->post(
                $baseUrl.'/mpesa/stkpushquery/v1/query',
                [
                    'BusinessShortCode' => $shortcode,
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'CheckoutRequestID' => $payment->provider_checkout_request_id,
                ]
            );

        $response->throw();

        $data = $response->json();

        if (
            ! is_array($data) ||
            ($data['ResponseCode'] ?? null) !== '0'
        ) {
            throw new RuntimeException(
                'Daraja did not accept the STK Push query.'
            );
        }

        return $data;
    }
}
