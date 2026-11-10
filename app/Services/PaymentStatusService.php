<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentStatusService
{
    public function markSuccessful(Payment $payment, ?string $providerReference): Payment
    {
        return DB::transaction(function () use ($payment, $providerReference) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status !== Payment::STATUS_PENDING) {
                throw new InvalidArgumentException('Only pending payments can be marked successful.');
            }
            if (
                $providerReference !== null &&
                Payment::query()
                ->where('provider', $payment->provider)
                ->where('provider_reference', $providerReference)
                ->exists()
            ) {
                throw new InvalidArgumentException(
                    'This provider transaction reference has already been used.'
                );
            }

            $payment->update([
                'status' => Payment::STATUS_SUCCESSFUL,
                'provider_reference' => $providerReference,
                'paid_at' => now(),
            ]);

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($payment->order_id);

            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'paid_at' => $payment->paid_at,
            ]);

            return $payment->refresh();
        });
    }

    public function markFailed(Payment $payment, string $failureReason): Payment
    {
        return DB::transaction(function () use ($payment, $failureReason) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status !== Payment::STATUS_PENDING) {
                throw new InvalidArgumentException('Only pending payments can be marked failed.');
            }

            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'failure_reason' => $failureReason
            ]);

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($payment->order_id);

            //Do not mark the entire order as failed if another
            //payment attempt is still pennding or has succeeded
            $hasOtherActiveAttempt = $order->payments()
                ->where('id', '!=', $payment->id)
                ->whereIn('status', [
                    Payment::STATUS_PENDING,
                    Payment::STATUS_SUCCESSFUL,
                ])->exists();

            if (! $hasOtherActiveAttempt) {
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_FAILED,
                ]);
            }

            return $payment->refresh();
        });
    }
}
