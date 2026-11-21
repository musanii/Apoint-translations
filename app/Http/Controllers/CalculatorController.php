<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\Language;
use App\Models\PricingRule;
use App\Models\Service;
use App\Services\QuoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CalculatorController extends Controller
{
    public function options(): JsonResponse
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'native_name']);

        $currencies = Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'symbol', 'decimal_places', 'is_default']);

        $pricingOptions = PricingRule::query()
            ->with(['service', 'currency', 'sourceLanguage', 'targetLanguage'])
            ->where('is_active', true)
            ->whereHas('service', fn ($q) => $q->where('is_active', true))
            ->whereHas('currency', fn ($q) => $q->where('is_active', true))
            ->where(function ($q) {
                $q->whereNull('source_language_id')
                    ->orWhereHas('sourceLanguage', fn ($language) => $language->where('is_active', true)
                    );
            })
            ->where(function ($q) {
                $q->whereNull('target_language_id')
                    ->orWhereHas('targetLanguage', fn ($language) => $language->where('is_active', true)
                    );
            })
            ->get()
            ->map(fn ($rule) => [
                'service' => [
                    'id' => $rule->service->id,
                    'name' => $rule->service->name,
                ],
                'source_language' => $rule->sourceLanguage ? [
                    'id' => $rule->sourceLanguage->id,
                    'name' => $rule->sourceLanguage->name,
                ] : null,
                'target_language' => $rule->targetLanguage ? [
                    'id' => $rule->targetLanguage->id,
                    'name' => $rule->targetLanguage->name,
                ] : null,
                'currency' => [
                    'id' => $rule->currency->id,
                    'code' => $rule->currency->code,
                    'symbol' => $rule->currency->symbol,
                    'decimal_places' => $rule->currency->decimal_places,
                ],
                'pricing_type' => $rule->pricing_type,
                'unit' => $rule->unit,
                'description' => $rule->description,
            ])
            ->values();

        return response()->json([
            'services' => $services,
            'languages' => $languages,
            'currencies' => $currencies,
            'pricing_options' => $pricingOptions,
        ]);
    }

    public function calculate(Request $request, QuoteService $quoteService): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'source_language_id' => ['present', 'nullable', 'integer', 'exists:languages,id'],
            'target_language_id' => ['present', 'nullable', 'integer', 'exists:languages,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
        ]);

        try {
            $estimate = $quoteService->calculate(
                (int) $validated['service_id'],
                $validated['source_language_id'] !== null
                    ? (int) $validated['source_language_id']
                    : null,
                $validated['target_language_id'] !== null
                    ? (int) $validated['target_language_id']
                    : null,
                (int) $validated['currency_id'],
                (int) $validated['quantity'],
            );

            return response()->json(['estimate' => $estimate]);
        } catch (ModelNotFoundException|InvalidArgumentException $exception) {
            return response()->json([
                'message' => 'A price is not currently available for that selection',
            ], 422);
        }
    }
}
