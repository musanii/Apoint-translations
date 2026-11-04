<?php

namespace App\Services;

use App\Models\PricingRule;
use InvalidArgumentException;

class PricingCalculator
{
    public function calculate(
        PricingRule $pricingRule,
        int $quantity
    ): array {
        // 1. Validate quantity
        if ($quantity < 1) {
            throw new InvalidArgumentException(
                'Quantity must be at least 1.'
            );
        }

        // 2. Validate pricing rule
        if (! $pricingRule->is_active) {
            throw new InvalidArgumentException(
                'This pricing rule is not active.'
            );
        }

        // 3. Validate currency
        if (! $pricingRule->currency || ! $pricingRule->currency->is_active) {
            throw new InvalidArgumentException(
                'The pricing currency is not available.'
            );
        }

        // 4. Validate service
        if (! $pricingRule->service || ! $pricingRule->service->is_active) {
            throw new InvalidArgumentException(
                'The pricing service is not available.'
            );
        }

        // 5. Validate amounts
        if ((float) $pricingRule->rate < 0) {
            throw new InvalidArgumentException(
                'Pricing rate cannot be negative.'
            );
        }

        if ((float) ($pricingRule->minimum_charge ?? 0) < 0) {
            throw new InvalidArgumentException(
                'Minimum charge cannot be negative.'
            );
        }

        // 6. Calculate subtotal
        $subtotal = match ($pricingRule->pricing_type) {
            'per_word', 'per_page' => $quantity * (float) $pricingRule->rate,
            'fixed' => (float) $pricingRule->rate,
            default => throw new InvalidArgumentException(
                'Unsupported pricing type.'
            ),
        };

        // 7. Apply minimum charge
        $minimumCharge = (float) ($pricingRule->minimum_charge ?? 0);

        $total = max($subtotal, $minimumCharge);

        // 8. Apply currency precision
        $decimalPlaces = $pricingRule->currency->decimal_places;

        return [
            'pricing_type' => $pricingRule->pricing_type,
            'unit' => $pricingRule->unit,
            'quantity' => $quantity,
            'rate' => (float) $pricingRule->rate,
            'subtotal' => round($subtotal, 4),
            'minimum_charge' => $minimumCharge,
            'total' => round($total, $decimalPlaces),
            'currency' => $pricingRule->currency->code,
        ];
    }
}
