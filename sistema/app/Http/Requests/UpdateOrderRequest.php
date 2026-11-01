<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && $this->user() !== null
            && $order->canBeEditedBy($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $order = $this->route('order');
        $isDelivered = $order instanceof Order && $order->isDelivered();
        $setting = Setting::current();
        $autoPrice = (bool) $setting->auto_price;
        $referencePrice = (float) (filled($this->input('price_per_lb'))
            ? $this->input('price_per_lb')
            : (($order instanceof Order && (float) $order->price_per_lb > 0) ? $order->price_per_lb : $setting->price_per_lb));
        $weightCanBeDerived = $isDelivered && ! $autoPrice && $referencePrice > 0;

        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'delivery_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'weights' => ['nullable', 'array'],
            'weights.*' => [Rule::requiredIf($isDelivered && ! $weightCanBeDerived), 'nullable', 'numeric', 'min:0.01', 'max:9999.99', 'decimal:0,2'],
            'price_per_lb' => [Rule::requiredIf($isDelivered && $autoPrice), 'nullable', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
            'prices' => [Rule::requiredIf($isDelivered && ! $autoPrice), 'nullable', 'array'],
            'prices.*' => [Rule::requiredIf($isDelivered && ! $autoPrice), 'nullable', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
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
            'customer_id.required' => 'Selecciona un cliente.',
            'customer_id.exists' => 'El cliente seleccionado no existe.',
            'quantity.required' => 'Indica cuántos pollos tiene el pedido.',
            'quantity.integer' => 'La cantidad de pollos debe ser un número entero.',
            'quantity.min' => 'El pedido debe tener al menos un pollo.',
            'quantity.max' => 'Puedes registrar como máximo 100 pollos por pedido.',
            'delivery_date.date' => 'La fecha de entrega no es válida.',
            'note.max' => 'La nota no puede superar los 1000 caracteres.',
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
            'customer_id' => 'cliente',
            'quantity' => 'cantidad de pollos',
            'delivery_date' => 'fecha de entrega',
            'note' => 'nota',
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
