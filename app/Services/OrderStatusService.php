<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStatusService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function confirm(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (
                $order->status !== Order::STATUS_PENDING_PAYMENT ||
                $order->payment_status !== Order::PAYMENT_STATUS_PAID
            ) {
                throw new InvalidArgumentException(
                    'Only paid orders awaiting payment can be confirmed.'
                );
            }

            $order->update([
                'status' => Order::STATUS_CONFIRMED,
            ]);

            return $order;
        });
    }

    public function startProcessing(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->status !== Order::STATUS_CONFIRMED) {
                throw new InvalidArgumentException(
                    'Only confirmed orders can start processing.'
                );
            }

            $order->update([
                'status' => Order::STATUS_IN_PROGRESS,
            ]);
            $order->loadMissing('user');
            $this->notifications->user($order->user, 'Work has started', 'Our team has started processing order '.$order->reference.'.', route('orders.show', $order), 'order');

            return $order;
        });
    }

    public function complete(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->status !== Order::STATUS_IN_PROGRESS) {
                throw new InvalidArgumentException(
                    'Only orders in progress can be completed.'
                );
            }

            $order->update([
                'status' => Order::STATUS_COMPLETED,
            ]);
            $order->loadMissing('user');
            $this->notifications->user($order->user, 'Your order is complete', 'Order '.$order->reference.' has been completed. Please check your order page for collection or delivery details.', route('orders.show', $order), 'order', true);

            return $order;
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (
                $order->status !== Order::STATUS_PENDING_PAYMENT ||
                $order->payment_status !== Order::PAYMENT_STATUS_UNPAID
            ) {
                throw new InvalidArgumentException(
                    'Only orders that have not had a payment attempt initiated can be cancelled. Orders with payment activity must be reconciled first.'
                );
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
            ]);
            $order->loadMissing('user');
            $this->notifications->user($order->user, 'Your order was cancelled', 'Order '.$order->reference.' has been cancelled. Please contact Apoint if you need assistance.', route('orders.show', $order), 'order', true);

            return $order;
        });
    }
}
