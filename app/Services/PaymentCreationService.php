<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentCreationService
{
    public function createFor(
        Order $order,
        string $provider,
        string $paymentMethod,
        ?string $providerChannelId = null
    ): Payment {
        return DB::transaction(function () use (
            $order,
            $provider,
            $paymentMethod,
            $providerChannelId
        ) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (
                $order->status !== Order::STATUS_PENDING_PAYMENT ||
                ! in_array($order->payment_status, [
                    Order::PAYMENT_STATUS_UNPAID,
                    Order::PAYMENT_STATUS_FAILED,
                ], true)
            ) {
                throw new InvalidArgumentException(
                    'This order is not eligible for payment.'
                );
            }

            if (
                $order->payments()
                    ->where('status', Payment::STATUS_PENDING)
                    ->exists()
            ) {
                throw new InvalidArgumentException(
                    'This order already has a pending payment attempt.'
                );
            }

            $supportedCombinations = [
                'daraja' => ['mpesa'],
                'payhero' => ['mpesa'],
                'flutterwave' => ['card'],
            ];

            if (
                ! isset($supportedCombinations[$provider]) ||
                ! in_array(
                    $paymentMethod,
                    $supportedCombinations[$provider],
                    true
                )
            ) {
                throw new InvalidArgumentException(
                    'Unsupported payment provider and payment method combination.'
                );
            }

            if ($provider !== 'payhero' && $providerChannelId !== null) {
                throw new InvalidArgumentException(
                    'A provider channel ID is only supported for PayHero.'
                );
            }

            $payment = $order->payments()->create([
                'currency_id' => $order->currency_id,
                'reference' => 'APT-' . fake()->unique()->numerify('##########'),
                'provider' => $provider,
                'payment_method' => $paymentMethod,
                'provider_channel_id' => $providerChannelId,
                'amount' => $order->total,
                'status' => Payment::STATUS_PENDING,
            ]);

            $order->update([
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
            ]);

            return $payment;
        });
    }
}
