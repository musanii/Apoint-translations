<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'reference' => 'APT-R-' . now()->year . '-' . fake()->unique()->numerify('######'),
            'provider_reference' => null,
            'amount' => '1000.00',
            'status' => Refund::STATUS_PENDING,
            'reason' => null,
            'failure_reason' => null,
            'initiated_at' => now(),
            'completed_at' => null,
        ];
    }
}
