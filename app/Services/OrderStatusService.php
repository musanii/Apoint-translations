<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderStatusService
{
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

            return $order;
        });
    }

    public function cancel(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            if (! in_array($order->status, [
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_CONFIRMED,
            ], true)) {
                throw new InvalidArgumentException(
                    'Only orders awaiting payment or confirmed orders can be cancelled.'
                );
            }

            $order->update([
                'status' => Order::STATUS_CANCELLED,
            ]);

            return $order;
        });
    }
}
