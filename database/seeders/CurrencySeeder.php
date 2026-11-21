<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;
use Io238\ISOCountries\Models\Currency as IsoCurrency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencyCodes = [
            'KES',
            'USD',
            'GBP',
            'EUR',
            'CAD',
            'AED',
        ];

        foreach ($currencyCodes as $code) {
            $isoCurrency = IsoCurrency::findOrFail($code);

            Currency::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $isoCurrency->name,
                    'symbol' => $isoCurrency->symbol,
                    'decimal_places' => $isoCurrency->decimal_digits,
                    'is_active' => true,
                    'is_default' => $code === 'KES',

                ]
            );
        }
    }
}
