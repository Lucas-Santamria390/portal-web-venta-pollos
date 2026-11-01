<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SellNowRequest extends FormRequest
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
        $setting = Setting::current();
        $autoPrice = (bool) $setting->auto_price;
        $referencePrice = (float) (filled($this->input('price_per_lb')) ? $this->input('price_per_lb') : $setting->price_per_lb);
        $weightCanBeDerived = ! $autoPrice && $referencePrice > 0;

        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
            'weights' => [Rule::requiredIf(! $weightCanBeDerived), 'nullable', 'array'],
            'weights.*' => [Rule::requiredIf(! $weightCanBeDerived), 'nullable', 'numeric', 'min:0.01', 'max:9999.99', 'decimal:0,2'],
            'price_per_lb' => [Rule::requiredIf($autoPrice), 'nullable', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
            'prices' => [Rule::requiredIf(! $autoPrice), 'nullable', 'array'],
            'prices.*' => [Rule::requiredIf(! $autoPrice), 'nullable', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'paid_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => filled($this->input('customer_id')) ? (int) $this->input('customer_id') : null,
            'note' => filled($this->input('note')) ? trim((string) $this->input('note')) : null,
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
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'quantity.required' => 'Indica cuántos pollos se venden.',
            'quantity.integer' => 'La cantidad de pollos debe ser un número entero.',
            'quantity.min' => 'Vende al menos un pollo.',
            'quantity.max' => 'Puedes vender como máximo 100 pollos por pedido.',
            'note.max' => 'La nota no puede superar los 1000 caracteres.',
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
            'paid_amount.required' => 'Indica cuánto se paga en este momento.',
            'paid_amount.min' => 'El pago no puede ser negativo.',
            'paid_amount.decimal' => 'El pago admite como máximo dos decimales.',
            'method.required' => 'Selecciona un medio de pago.',
            'method.in' => 'Selecciona un medio de pago válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'quantity' => 'cantidad de pollos',
            'note' => 'nota',
            'weights' => 'pesos',
            'weights.*' => 'peso',
            'price_per_lb' => 'precio por libra',
            'prices' => 'precios',
            'prices.*' => 'precio',
            'paid_amount' => 'pago',
            'method' => 'medio de pago',
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
