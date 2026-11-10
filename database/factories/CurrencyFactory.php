<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;
use Override;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    protected $faker;


    public function __construct(...$args)
    {
       parent::__construct(...$args);
       $this->faker = fake();
    }

    public function definition(): array
    {
        return [
           'code' => $this->faker->unique()->lexify('???'),
            'name' => fake()->currencyCode(),
            'symbol' => '$',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => false,
        ];
    }
}