<?php

namespace App\Services;

use App\Models\Payment;
use InvalidArgumentException;

class MpesaCallbackService
{
    public function __construct(
        private readonly PaymentStatusService $paymentStatusService,
        private readonly MpesaStkPushQueryService $stkPushQueryService
    ) {}

    public function handle(array $callback): ?Payment
    {
        $stkCallback = $callback['Body']['stkCallback'] ?? null;

        if (! is_array($stkCallback)) {
            throw new InvalidArgumentException(
                'Invalid M-Pesa callback payload.'
            );
        }

        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? null;

        if (! is_string($checkoutRequestId) || $checkoutRequestId === '') {
            throw new InvalidArgumentException(
                'The callback is missing its checkout request ID.'
            );
        }

        $payment = Payment::query()
            ->where('provider', 'mpesa')
            ->where('provider_checkout_request_id', $checkoutRequestId)
            ->first();

        if (! $payment) {
            return null;
        }

        $merchantRequestId = $stkCallback['MerchantRequestID'] ?? null;

        if (
            ! is_string($merchantRequestId) ||
            $merchantRequestId !== $payment->provider_merchant_request_id
        ) {
            throw new InvalidArgumentException(
                'The callback merchant request ID does not match the payment.'
            );
        }

        // Ignore successful payments that already have
        // a provider transaction reference.
        if (
            $payment->status === Payment::STATUS_SUCCESSFUL &&
            $payment->provider_reference !== null
        ) {
            return $payment;
        }

        $resultCode = $stkCallback['ResultCode'] ?? null;

        if (! is_numeric($resultCode)) {
            throw new InvalidArgumentException(
                'The callback has an invalid result code.'
            );
        }

        if ((int) $resultCode !== 0) {
            if ($payment->status === Payment::STATUS_FAILED) {
                return $payment;
            }
            $failureReason = $stkCallback['ResultDesc'] ?? null;

            if (! is_string($failureReason) || $failureReason === '') {
                $failureReason = 'M-Pesa payment failed.';
            }

            return $this->paymentStatusService->markFailed(
                $payment,
                $failureReason
            );
        }

        $items = $stkCallback['CallbackMetadata']['Item'] ?? null;

        if (! is_array($items)) {
            throw new InvalidArgumentException(
                'The successful callback has invalid payment metadata.'
            );
        }

        $metadata = [];

        foreach ($items as $item) {
            if (
                ! is_array($item) ||
                ! isset($item['Name']) ||
                ! is_string($item['Name'])
            ) {
                throw new InvalidArgumentException(
                    'The successful callback contains invalid metadata items.'
                );
            }

            $metadata[$item['Name']] = $item['Value'] ?? null;
        }

        $receiptNumber = $metadata['MpesaReceiptNumber'] ?? null;
        $amount = $metadata['Amount'] ?? null;

        if (! is_string($receiptNumber) || $receiptNumber === '') {
            throw new InvalidArgumentException(
                'The successful callback is missing its M-Pesa receipt number.'
            );
        }

        if (
            ! is_numeric($amount) ||
            (float) $amount !== (float) $payment->amount
        ) {
            throw new InvalidArgumentException(
                'The callback amount does not match the payment amount.'
            );
        }

        if ($payment->status === Payment::STATUS_SUCCESSFUL) {
            return $this->paymentStatusService->attachProviderReference(
                $payment,
                $receiptNumber
            );
        }

        if ($payment->status === Payment::STATUS_FAILED) {
            if (! $this->verifyLateSuccess($payment)) {
                return $payment;
            }

            return $this->paymentStatusService->recoverFailedPayment(
                $payment,
                $receiptNumber
            );
        }

        return $this->paymentStatusService->markSuccessful(
            $payment,
            $receiptNumber
        );
    }


    private function verifyLateSuccess(Payment $payment): bool
    {
        $result = $this->stkPushQueryService->verifyLateSuccess($payment);

        return ($result['CheckoutRequestID'] ?? null) === $payment->provider_checkout_request_id &&
            ($result['MerchantRequestID'] ?? null) === $payment->provider_merchant_request_id &&
            isset($result['ResultCode']) &&
            is_numeric($result['ResultCode']) &&
            (int) $result['ResultCode'] === 0;
    }
}
