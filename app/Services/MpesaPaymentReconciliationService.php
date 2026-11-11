<?php

namespace App\Services;

use App\Models\Payment;
use RuntimeException;

class MpesaPaymentReconciliationService
{
    private const TERMINAL_FAILURE_CODES = [
        '1',
        '1032',
        '1037',
        '2001',
    ];

    public function __construct(
        private readonly MpesaStkPushQueryService $queryService,
        private readonly PaymentStatusService $paymentStatusService
    ) {}

    public function reconcile(Payment $payment): Payment
    {
        $result = $this->queryService->query($payment);

        if (
            ($result['CheckoutRequestID'] ?? null)
            !== $payment->provider_checkout_request_id ||
            ($result['MerchantRequestID'] ?? null)
            !== $payment->provider_merchant_request_id
        ) {
            throw new RuntimeException(
                'Daraja returned a response that does not match this payment.'
            );
        }

        if (
            ! array_key_exists('ResultCode', $result) ||
            ! is_numeric($result['ResultCode'])
        ) {
            throw new RuntimeException(
                'Daraja returned an invalid or incomplete transaction result.'
            );
        }

        $resultCode = (string) $result['ResultCode'];

        if ($resultCode === '0') {
            return $this->paymentStatusService->markSuccessful(
                $payment,
                null
            );
        }

        if (! in_array($resultCode, self::TERMINAL_FAILURE_CODES, true)) {
            return $payment->refresh();
        }

        $failureReason = $result['ResultDesc'] ?? 'M-Pesa payment failed.';

        if (! is_string($failureReason) || trim($failureReason) === '') {
            $failureReason = 'M-Pesa payment failed.';
        }

        return $this->paymentStatusService->markFailed(
            $payment,
            $failureReason
        );
    }
}