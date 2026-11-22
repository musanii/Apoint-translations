<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentStatusService
{
    public function __construct(
        private readonly OrderStatusService $orderStatusService,
        private readonly NotificationService $notifications
    ) {}

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

            if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
                $order->update([
                    'payment_status' => Order::PAYMENT_STATUS_PAID,
                    'paid_at' => $payment->paid_at,
                ]);
            }

            if ($order->status === Order::STATUS_PENDING_PAYMENT) {
                $this->orderStatusService->confirm($order);
            }

            $order->loadMissing('user');
            $this->notifications->user($order->user, 'Payment received', 'We have received payment for order '.$order->reference.'. Your order is confirmed.', route('orders.show', $order), 'payment', true);
            $this->notifications->admins('Payment received', 'Payment for order '.$order->reference.' has been confirmed.', route('admin.dashboard'), 'payment', true);

            return $payment->refresh();
        });
    }

    public function recoverFailedPayment(
        Payment $payment,
        string $providerReference
    ): Payment {
        return DB::transaction(function () use ($payment, $providerReference) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status !== Payment::STATUS_FAILED) {
                throw new InvalidArgumentException(
                    'Only failed payments can be recovered.'
                );
            }

            if (trim($providerReference) === '') {
                throw new InvalidArgumentException(
                    'The provider transaction reference cannot be empty.'
                );
            }

            if (
                Payment::query()
                    ->where('provider', $payment->provider)
                    ->where('provider_reference', $providerReference)
                    ->exists()
            ) {
                throw new InvalidArgumentException(
                    'This provider transaction reference has already been used.'
                );
            }

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($payment->order_id);

            if ($order->status !== Order::STATUS_PENDING_PAYMENT) {
                throw new InvalidArgumentException(
                    'Only orders awaiting payment can recover a failed payment.'
                );
            }

            if (! in_array($order->payment_status, [
                Order::PAYMENT_STATUS_FAILED,
                Order::PAYMENT_STATUS_PENDING,
            ], true)) {
                throw new InvalidArgumentException(
                    'Only orders with failed or pending payment status can recover a failed payment.'
                );
            }

            $hasOtherSuccessfulPayment = $order->payments()
                ->where('id', '!=', $payment->id)
                ->where('status', Payment::STATUS_SUCCESSFUL)
                ->exists();

            if ($hasOtherSuccessfulPayment) {
                throw new InvalidArgumentException(
                    'Another payment attempt has already succeeded for this order.'
                );
            }

            $payment->update([
                'status' => Payment::STATUS_SUCCESSFUL,
                'provider_reference' => $providerReference,
                'paid_at' => now(),
                'failure_reason' => null,
            ]);

            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PAID,
                'paid_at' => $payment->paid_at,
            ]);

            $this->orderStatusService->confirm($order);
            $order->loadMissing('user');
            $this->notifications->user($order->user, 'Payment received', 'We have received payment for order '.$order->reference.'. Your order is confirmed.', route('orders.show', $order), 'payment', true);
            $this->notifications->admins('Payment received', 'Payment for order '.$order->reference.' has been confirmed.', route('admin.dashboard'), 'payment', true);

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
                'failure_reason' => $failureReason,
            ]);

            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($payment->order_id);

            // Do not mark the entire order as failed if another
            // payment attempt is still pennding or has succeeded
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
                $order->loadMissing('user');
                $this->notifications->user($order->user, 'Payment was unsuccessful', 'We could not confirm payment for order '.$order->reference.'. Please review the payment details or contact Apoint for assistance.', route('orders.show', $order), 'payment', true);
                $this->notifications->admins('Payment failed', 'A payment attempt for order '.$order->reference.' failed.', route('admin.dashboard'), 'payment', true);
            }

            return $payment->refresh();
        });
    }

    public function attachProviderReference(
        Payment $payment,
        string $providerReference
    ): Payment {
        return DB::transaction(function () use ($payment, $providerReference) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status !== Payment::STATUS_SUCCESSFUL) {
                throw new InvalidArgumentException(
                    'Only successful payments can receive a provider reference.'
                );
            }

            if (trim($providerReference) === '') {
                throw new InvalidArgumentException(
                    'The provider transaction reference cannot be empty.'
                );
            }

            if ($payment->provider_reference !== null) {
                if ($payment->provider_reference === $providerReference) {
                    return $payment;
                }

                throw new InvalidArgumentException(
                    'This payment already has a different provider transaction reference.'
                );
            }

            if (
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
                'provider_reference' => $providerReference,
            ]);

            return $payment->refresh();
        });
    }
}
