<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequestRequest;
use App\Models\Currency;
use App\Models\Language;
use App\Models\PricingRule;
use App\Models\QuoteRequest;
use App\Models\Service;
use App\Services\QuoteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class QuoteRequestController extends Controller
{
    public function create(Request $request): Response
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        $pricingRules = PricingRule::query()
            ->whereIn('service_id', $services->modelKeys())
            ->where('is_active', true)
            ->get(['service_id', 'pricing_type', 'unit'])
            ->groupBy('service_id');

        $services->each(function (Service $service) use ($pricingRules): void {
            $rules = $pricingRules->get($service->id, collect());
            $units = $rules->pluck('unit')->filter()->unique();

            $service->setAttribute(
                'quantity_unit',
                $units->count() === 1 ? $units->first() : null
            );
            $service->setAttribute(
                'quantity_is_fixed',
                $rules->isNotEmpty() && $rules->every(
                    fn (PricingRule $rule): bool => $rule->pricing_type === 'fixed'
                )
            );
        });

        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'native_name']);

        $currencies = Currency::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'symbol',
                'decimal_places',
                'is_default',
            ]);

        $initial = [
            'service_id' => $request->integer('service_id') ?: null,
            'source_language_id' => $request->integer('source_language_id') ?: null,
            'target_language_id' => $request->integer('target_language_id') ?: null,
            'currency_id' => $request->integer('currency_id') ?: null,
            'quantity' => $request->integer('quantity') ?: null,
        ];

        // Ignore query-string IDs that are not among the active options.
        $initial['service_id'] = $services->contains('id', $initial['service_id'])
            ? $initial['service_id']
            : null;

        $initial['source_language_id'] = $languages->contains('id', $initial['source_language_id'])
            ? $initial['source_language_id']
            : null;

        $initial['target_language_id'] = $languages->contains('id', $initial['target_language_id'])
            ? $initial['target_language_id']
            : null;

        $initial['currency_id'] = $currencies->contains('id', $initial['currency_id'])
            ? $initial['currency_id']
            : null;

        return Inertia::render('quote-requests/create', [
            'services' => $services,
            'languages' => $languages,
            'currencies' => $currencies,
            'initial' => $initial,
        ]);
    }

    public function store(
        StoreQuoteRequestRequest $request,
        QuoteService $quoteService
    ): RedirectResponse {
        $data = $request->validated();

        $pricingRule = null;

        try {
            $pricingRule = $quoteService->findPricingRule(
                (int) $data['service_id'],
                $data['source_language_id'] !== null
                    ? (int) $data['source_language_id']
                    : null,
                $data['target_language_id'] !== null
                    ? (int) $data['target_language_id']
                    : null,
                (int) $data['currency_id'],
            );
        } catch (ModelNotFoundException) {
            // A request without an instant pricing rule is still valid.
        }

        $unit = $pricingRule?->pricing_type === 'fixed'
            ? null
            : ($pricingRule?->unit ?? $quoteService->inferUnitForService((int) $data['service_id']));

        $quoteRequest = QuoteRequest::create([
            'user_id' => (int) $request->user()->getAuthIdentifier(),
            'service_id' => $data['service_id'],
            'source_language_id' => $data['source_language_id'],
            'target_language_id' => $data['target_language_id'],
            'currency_id' => $data['currency_id'],
            'quantity' => $pricingRule?->pricing_type === 'fixed'
                ? null
                : ($data['quantity'] ?? null),
            'unit' => $unit,
            'delivery_date' => $data['delivery_date'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'delivery_method' => $data['delivery_method'],
            'recipient_name' => $data['delivery_method'] === 'physical'
                ? $data['recipient_name']
                : null,
            'recipient_phone' => $data['delivery_method'] === 'physical'
                ? $data['recipient_phone']
                : null,
            'delivery_country' => $data['delivery_method'] === 'physical'
                ? $data['delivery_country']
                : null,
            'delivery_address' => $data['delivery_method'] === 'physical'
                ? $data['delivery_address']
                : null,
            'delivery_postal_code' => $data['delivery_method'] === 'physical'
                ? ($data['delivery_postal_code'] ?? null)
                : null,
        ]);

        return redirect()
            ->route('quote-requests.show', $quoteRequest)
            ->with('success', 'Your quote request has been submitted. You can upload your documents below.');
    }

    public function show(QuoteRequest $quoteRequest): Response
    {
        Gate::authorize('view', $quoteRequest);

        $quoteRequest->load([
            'service:id,name',
            'sourceLanguage:id,name',
            'targetLanguage:id,name',
            'currency:id,code,symbol',
            'documents:id,quote_request_id,original_name,extension,size,created_at',
        ]);

        return Inertia::render('quote-requests/show', [
            'quoteRequest' => $quoteRequest,
        ]);
    }
}
