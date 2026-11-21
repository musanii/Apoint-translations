<?php

namespace App\Http\Requests;

use App\Models\QuoteRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', QuoteRequest::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')->where('is_active', true),
            ],

            'source_language_id' => [
                'present',
                'nullable',
                'integer',
                'required_with:target_language_id',
                Rule::exists('languages', 'id')->where('is_active', true),
            ],

            'target_language_id' => [
                'present',
                'nullable',
                'integer',
                'required_with:source_language_id',
                Rule::exists('languages', 'id')->where('is_active', true),
            ],

            'currency_id' => [
                'required',
                'integer',
                Rule::exists('currencies', 'id')->where('is_active', true),
            ],

            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'instructions' => ['nullable', 'string', 'max:5000'],

            'delivery_method' => [
                'required',
                Rule::in(['digital', 'physical']),
            ],

            'recipient_name' => [
                'required_if:delivery_method,physical',
                'nullable',
                'string',
                'max:255',
            ],

            'recipient_phone' => [
                'required_if:delivery_method,physical',
                'nullable',
                'string',
                'max:30',
            ],

            'delivery_country' => [
                'required_if:delivery_method,physical',
                'nullable',
                'string',
                'max:100',
            ],

            'delivery_address' => [
                'required_if:delivery_method,physical',
                'nullable',
                'string',
                'max:255',
            ],

            'delivery_postal_code' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }
}
