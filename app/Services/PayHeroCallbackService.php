<?php

namespace App\Services;

use App\Models\Payment;
use InvalidArgumentException;

class PayHeroCallbackService
{
    public function __construct(
        private readonly PaymentStatusService $paymentStatusService,
        private readonly PayHeroTransactionStatusService $transactionStatusService
    ) {}

    public function handle(array $callback): ?Payment
    {
        $response = $callback['response'] ?? null;

        if (! is_array($response)) {
            throw new InvalidArgumentException(
                'Invalid PayHero callback payload.'
            );
        }

        if (($callback['status'] ?? null) !== true) {
            throw new InvalidArgumentException(
                'The PayHero callback indicates an unsuccessful request.'
            );
        }

        $checkoutRequestId = $response['CheckoutRequestID'] ?? null;

        if (
            ! is_string($checkoutRequestId) ||
            $checkoutRequestId === ''
        ) {
            throw new InvalidArgumentException(
                'The callback is missing its checkout request ID.'
            );
        }

        $payment = Payment::query()
            ->where('provider', 'payhero')
            ->where('payment_method', 'mpesa')
            ->where(
                'provider_checkout_request_id',
                $checkoutRequestId
            )
            ->first();

        if (! $payment) {
            return null;
        }

        if (
            ($response['ExternalReference'] ?? null) !==
            $payment->reference
        ) {
            throw new InvalidArgumentException(
                'The callback external reference does not match the payment.'
            );
        }

        $resultCode = $response['ResultCode'] ?? null;

        if (
            ! is_numeric($resultCode) ||
            (float) $resultCode !== (float) (int) $resultCode
        ) {
            throw new InvalidArgumentException(
                'The callback has an invalid result code.'
            );
        }

        $status = $response['Status'] ?? null;

        if (! is_string($status) || $status === '') {
            throw new InvalidArgumentException(
                'The callback is missing its payment status.'
            );
        }

        $isSuccessful = (int) $resultCode === 0
            && $status === 'Success';

        $isFailed = (int) $resultCode !== 0
            && $status === 'Failed';

        if (! $isSuccessful && ! $isFailed) {
            throw new InvalidArgumentException(
                'The callback contains contradictory payment status information.'
            );
        }

        $amount = $response['Amount'] ?? null;

        if (
            ! is_numeric($amount) ||
            (float) $amount !== (float) $payment->amount
        ) {
            throw new InvalidArgumentException(
                'The callback amount does not match the payment amount.'
            );
        }

        // Handle legitimate failed transactions.
        if ($isFailed) {
            // Do not downgrade successful payments or process
            // duplicate failure callbacks again.
            if ($payment->status !== Payment::STATUS_PENDING) {
                return $payment;
            }

            $failureReason = $response['ResultDesc'] ?? null;

            if (! is_string($failureReason) || $failureReason === '') {
                $failureReason = 'PayHero reported an unsuccessful M-Pesa payment.';
            }

            return $this->paymentStatusService->markFailed(
                $payment,
                $failureReason
            );
        }

        // Everything below this point handles successful payments.
        $receiptNumber = $response['MpesaReceiptNumber'] ?? null;

        if (
            ! is_string($receiptNumber) ||
            $receiptNumber === ''
        ) {
            throw new InvalidArgumentException(
                'The successful callback is missing its M-Pesa receipt number.'
            );
        }

        if ($payment->status === Payment::STATUS_SUCCESSFUL) {
            return $this->paymentStatusService->attachProviderReference(
                $payment,
                $receiptNumber
            );
        }

        if ($payment->status === Payment::STATUS_FAILED) {
            $verifiedTransaction = $this->transactionStatusService
                ->verifyLateSuccess($payment);

            $verifiedReceipt = $verifiedTransaction['provider_reference'] ?? null;

            if (
                ! is_string($verifiedReceipt) ||
                $verifiedReceipt === '' ||
                $verifiedReceipt !== $receiptNumber
            ) {
                throw new InvalidArgumentException(
                    'The PayHero verified receipt does not match the callback receipt.'
                );
            }

            return $this->paymentStatusService->recoverFailedPayment(
                $payment,
                $verifiedReceipt
            );
        }

        return $this->paymentStatusService->markSuccessful(
            $payment,
            $receiptNumber
        );
    }
}
