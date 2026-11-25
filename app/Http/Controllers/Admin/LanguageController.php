<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LanguageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/languages/index', [
            'languages' => Language::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Language::create($this->validated($request));
        return back()->with('success', 'Language created.');
    }

    public function update(Request $request, Language $language): RedirectResponse
    {
        $language->update($this->validated($request, $language));
        return back()->with('success', 'Language updated.');
    }

    private function validated(Request $request, ?Language $language = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:12', 'alpha_dash', Rule::unique('languages', 'code')->ignore($language?->id)],
            'native_name' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);
    }
}
