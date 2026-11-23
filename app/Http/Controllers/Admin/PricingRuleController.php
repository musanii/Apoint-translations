<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\Language;
use App\Models\PricingRule;
use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PricingRuleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/pricing-rules/index', [
            'rules' => PricingRule::query()->with(['service:id,name', 'currency:id,code,symbol', 'sourceLanguage:id,name', 'targetLanguage:id,name'])->orderBy('service_id')->orderBy('currency_id')->get(),
            'services' => Service::query()->orderBy('name')->get(['id', 'name']),
            'languages' => Language::query()->orderBy('name')->get(['id', 'name', 'code']),
            'currencies' => Currency::query()->orderBy('code')->get(['id', 'code', 'symbol']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureUniqueCombination($data);
        PricingRule::create($data);
        return back()->with('success', 'Pricing rule created.');
    }

    public function update(Request $request, PricingRule $pricingRule): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureUniqueCombination($data, $pricingRule);
        $pricingRule->update($data);
        return back()->with('success', 'Pricing rule updated.');
    }

    public function destroy(PricingRule $pricingRule): RedirectResponse
    {
        $pricingRule->delete();
        return back()->with('success', 'Pricing rule deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'source_language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'target_language_id' => ['nullable', 'integer', 'exists:languages,id'],
            'pricing_type' => ['required', 'string', 'in:fixed,per_word,per_page'],
            'unit' => ['required', 'string', 'max:40'],
            'rate' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'minimum_charge' => ['nullable', 'numeric', 'gte:0', 'max:999999999'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function ensureUniqueCombination(array $data, ?PricingRule $ignore = null): void
    {
        $query = PricingRule::query()
            ->where('service_id', $data['service_id'])
            ->where('currency_id', $data['currency_id'])
            ->where('pricing_type', $data['pricing_type'])
            ->when($ignore, fn (Builder $q) => $q->where('id', '!=', $ignore->id));

        foreach (['source_language_id', 'target_language_id'] as $field) {
            $data[$field] === null
                ? $query->whereNull($field)
                : $query->where($field, $data[$field]);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'pricing_type' => 'A rule already exists for this service, currency and language pair.',
            ]);
        }
    }
}
