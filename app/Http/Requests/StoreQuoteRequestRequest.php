<?php

namespace App\Http\Requests;

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
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
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
                'max:1000',
            ],

            'delivery_postal_code' => [
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }
}
