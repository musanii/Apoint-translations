<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/currencies/index', [
            'currencies' => Currency::query()->orderByDesc('is_default')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->ensureDefaultIsActive($data);

        DB::transaction(function () use ($data): void {
            if ($data['is_default']) {
                Currency::query()->update(['is_default' => false]);
            }

            Currency::create($data);
        });

        return back()->with('success', 'Currency created.');
    }

    public function update(Request $request, Currency $currency): RedirectResponse
    {
        $data = $this->validated($request, $currency);
        $this->ensureDefaultIsActive($data);

        if (
            $currency->is_default
            && ! $data['is_default']
            && ! Currency::query()
                ->where('id', '!=', $currency->id)
                ->where('is_default', true)
                ->where('is_active', true)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'is_default' => 'Set another active default currency before removing the current default.',
            ]);
        }

        DB::transaction(function () use ($currency, $data): void {
            if ($data['is_default']) {
                Currency::query()->where('id', '!=', $currency->id)->update(['is_default' => false]);
            }

            $currency->update($data);
        });

        return back()->with('success', 'Currency updated.');
    }

    private function ensureDefaultIsActive(array $data): void
    {
        if ($data['is_default'] && ! $data['is_active']) {
            throw ValidationException::withMessages([
                'is_default' => 'The default currency must also be active.',
            ]);
        }
    }

    private function validated(Request $request, ?Currency $currency = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'size:3', 'alpha', Rule::unique('currencies', 'code')->ignore($currency?->id)],
            'name' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'max:12'],
            'decimal_places' => ['required', 'integer', 'between:0,4'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
        ]);
    }
}
