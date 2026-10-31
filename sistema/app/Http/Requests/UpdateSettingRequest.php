<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'price_per_lb' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'auto_price' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_price' => $this->boolean('auto_price'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price_per_lb.required' => 'Indica el precio por libra.',
            'price_per_lb.numeric' => 'El precio por libra debe ser un número.',
            'price_per_lb.min' => 'El precio por libra no puede ser negativo.',
            'price_per_lb.decimal' => 'El precio por libra admite como máximo dos decimales.',
            'auto_price.required' => 'Indica si el precio se calcula automáticamente.',
            'auto_price.boolean' => 'La opción de precio automático no es válida.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'price_per_lb' => 'precio por libra',
            'auto_price' => 'precio automático',
        ];
    }
}
