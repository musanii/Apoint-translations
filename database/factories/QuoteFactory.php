<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    public function definition(): array
    {
        $user = User::factory()->create();

        $service = Service::create([
            'name' => 'Translation',
            'slug' => 'translation-' . fake()->unique()->numberBetween(1, 999999),
            'is_active' => true,
        ]);

        $currency = Currency::create([
            'code' => 'KES',
            'name' => 'Kenyan Shilling',
            'symbol' => 'KSh',
            'decimal_places' => 2,
            'is_active' => true,
        ]);

        $quoteRequest = QuoteRequest::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'currency_id' => $currency->id,
        ]);

        return [
            'quote_request_id' => $quoteRequest->id,
            'currency_id' => $currency->id,
            'reference' => 'APT-Q-' . now()->year . '-' . fake()->unique()->numerify('######'),
            'pricing_type' => 'per_word',
            'quantity' => 100,
            'unit' => 'word',
            'rate' => 2.5000,
            'subtotal' => 250.00,
            'total' => 250.00,
            'status' => Quote::STATUS_DRAFT,
        ];
    }
}
