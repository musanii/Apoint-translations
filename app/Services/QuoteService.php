<?php

namespace App\Services;

use App\Models\PricingRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QuoteService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private PricingCalculator $calculator) {}

    public function calculate(
        int $serviceId,
        int $sourceLanguageId,
        int $targetLanguageId,
        int $currencyId,
        int $quantity
    ): array {
        $pricingRule = PricingRule::query()
            ->where('service_id', $serviceId)
            ->where('source_language_id', $sourceLanguageId)
            ->where('target_language_id', $targetLanguageId)
            ->where('currency_id', $currencyId)
            ->where('is_active', true)
            ->whereHas('service', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('sourceLanguage', function ($query) {
                $query->where('is_active', true);
            })
            ->whereHas('targetLanguage', function ($query) {
                $query->where('is_active', true);
            })

            ->whereHas('currency', function ($query) {
                $query->where('is_active', true);
            })
            ->first();
        if (!$pricingRule) {
            throw (new ModelNotFoundException())
                ->setModel(PricingRule::class);
        }

        return $this->calculator->calculate($pricingRule, $quantity);
    }
}
