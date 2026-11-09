<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $quote = Quote::factory()->create();
        $quoteRequest = $quote->quoteRequest;

        return [
            'quote_id' => $quote->id,
            'user_id' => $quoteRequest->user_id,
            'currency_id' => $quote->currency_id,
            'reference' => 'APT-O-' . now()->year . '-' . fake()->unique()->numerify('######'),
            'subtotal' => $quote->subtotal,
            'express_fee' => 0,
            'certification_fee' => 0,
            'courier_fee' => 0,
            'total' => $quote->total,
            'delivery_method' => $quoteRequest->delivery_method,
            'recipient_name' => $quoteRequest->recipient_name,
            'recipient_phone' => $quoteRequest->recipient_phone,
            'delivery_country' => $quoteRequest->delivery_country,
            'delivery_address' => $quoteRequest->delivery_address,
            'delivery_postal_code' => $quoteRequest->delivery_postal_code,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Order::PAYMENT_STATUS_UNPAID,
        ];
    }
}
