<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use RuntimeException;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'currency_id' => Currency::firstOrCreate(
                ['code' => 'KES'],
                [
                    'name' => 'Kenyan Shilling',
                    'symbol' => 'KSh',
                    'decimal_places' => 2,
                    'is_active' => true,
                    'is_default' => true,
                ]
            )->id,
            'reference' => 'APT-P-' . fake()->unique()->numerify('##########'),
            'provider' => fake()->randomElement(['mpesa', 'flutterwave']),
            'provider_reference' => null,
            'amount' => fake()->randomFloat(2, 100, 50000),
            'status' => Payment::STATUS_PENDING,
            'failure_reason' => null,
            'paid_at' => null,
        ];
    }
}
