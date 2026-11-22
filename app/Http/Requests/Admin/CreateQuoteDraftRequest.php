<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class CreateQuoteDraftRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('admin') ?? false; }
    public function rules(): array {
        return [
            'pricing_type'=>['required',Rule::in(['per_word','per_page','fixed'])],
            'quantity'=>['required_if:pricing_type,per_word,per_page','nullable','numeric','min:0.01','max:1000000'],
            'unit'=>['required_if:pricing_type,per_word,per_page','nullable','string','max:30'],
            'rate'=>['required_if:pricing_type,per_word,per_page','nullable','numeric','min:0','max:100000000'],
            'subtotal'=>['required_if:pricing_type,fixed','nullable','numeric','min:0','max:100000000'],
            'express_fee'=>['nullable','numeric','min:0','max:100000000'],
            'certification_fee'=>['nullable','numeric','min:0','max:100000000'],
            'courier_fee'=>['nullable','numeric','min:0','max:100000000'],
            'customer_notes'=>['nullable','string','max:5000'],'admin_notes'=>['nullable','string','max:5000'],
        ];
    }
}
