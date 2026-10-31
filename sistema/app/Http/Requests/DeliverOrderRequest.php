<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliverOrderRequest extends FormRequest
{
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
        $autoPrice = (bool) Setting::current()->auto_price;

        return [
            'weights' => ['required', 'array'],
            'weights.*' => ['required', 'numeric', 'min:0.01', 'max:9999.99', 'decimal:0,2'],
            'price_per_lb' => [Rule::requiredIf($autoPrice), 'nullable', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
            'prices' => [Rule::requiredIf(! $autoPrice), 'nullable', 'array'],
            'prices.*' => [Rule::requiredIf(! $autoPrice), 'nullable', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'weights' => $this->normalizeList($this->input('weights', [])),
            'prices' => $this->normalizeList($this->input('prices', [])),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weights.required' => 'Registra el peso de cada pollo.',
            'weights.*.required' => 'Registra el peso de cada pollo.',
            'weights.*.numeric' => 'El peso debe ser un número.',
            'weights.*.min' => 'El peso debe ser mayor a cero.',
            'weights.*.decimal' => 'El peso admite como máximo dos decimales.',
            'price_per_lb.required' => 'Indica el precio por libra.',
            'price_per_lb.min' => 'El precio por libra debe ser mayor a cero.',
            'price_per_lb.decimal' => 'El precio por libra admite como máximo dos decimales.',
            'prices.*.required' => 'Indica el precio de cada pollo.',
            'prices.*.min' => 'El precio de cada pollo debe ser mayor a cero.',
            'prices.*.decimal' => 'El precio admite como máximo dos decimales.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'weights' => 'pesos',
            'weights.*' => 'peso',
            'price_per_lb' => 'precio por libra',
            'prices' => 'precios',
            'prices.*' => 'precio',
        ];
    }

    /**
     * Normalize a list of optional values, keeping its original indexes.
     *
     * @return array<int, string|null>
     */
    private function normalizeList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return collect($values)
            ->map(fn ($value): ?string => filled($value) ? trim((string) $value) : null)
            ->all();
    }
}
