<?php

namespace App\Services;

use App\Models\PricingRule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QuoteService
{
    public function __construct(
        private PricingCalculator $calculator
    ) {}

    public function findPricingRule(
        int $serviceId,
        ?int $sourceLanguageId,
        ?int $targetLanguageId,
        int $currencyId
    ): PricingRule {
        $query = PricingRule::query()
            ->where('service_id', $serviceId)
            ->where('currency_id', $currencyId)
            ->where('is_active', true)
            ->whereHas('service', fn ($q) => $q->where('is_active', true))
            ->whereHas('currency', fn ($q) => $q->where('is_active', true));

        if ($sourceLanguageId === null) {
            $query->whereNull('source_language_id');
        } else {
            $query->where('source_language_id', $sourceLanguageId)
                ->whereHas('sourceLanguage', fn ($q) => $q->where('is_active', true));
        }

        if ($targetLanguageId === null) {
            $query->whereNull('target_language_id');
        } else {
            $query->where('target_language_id', $targetLanguageId)
                ->whereHas('targetLanguage', fn ($q) => $q->where('is_active', true));
        }

        $pricingRule = $query->first();

        if (! $pricingRule) {
            throw (new ModelNotFoundException())
                ->setModel(PricingRule::class);
        }

        return $pricingRule;
    }

    /**
     * Infer a quantity unit for custom language or currency combinations.
     * Only return a unit when every active rule for the service agrees.
     */
    public function inferUnitForService(int $serviceId): ?string
    {
        $units = PricingRule::query()
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->whereNotNull('unit')
            ->distinct()
            ->pluck('unit')
            ->values();

        return $units->count() === 1 ? $units->first() : null;
    }

    public function calculate(
        int $serviceId,
        ?int $sourceLanguageId,
        ?int $targetLanguageId,
        int $currencyId,
        int $quantity
    ): array {
        $pricingRule = $this->findPricingRule(
            $serviceId,
            $sourceLanguageId,
            $targetLanguageId,
            $currencyId
        );

        return $this->calculator->calculate($pricingRule, $quantity);
    }
}
