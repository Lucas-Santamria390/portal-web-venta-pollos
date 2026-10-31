<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'delivery_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
            'weights' => ['nullable', 'array'],
            'weights.*' => ['nullable', 'numeric', 'min:0.01', 'max:9999.99', 'decimal:0,2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => filled($this->input('note')) ? trim((string) $this->input('note')) : null,
            'weights' => $this->normalizeList($this->input('weights', [])),
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
            'quantity.required' => 'Indica cuántos pollos se reservan.',
            'quantity.integer' => 'La cantidad de pollos debe ser un número entero.',
            'quantity.min' => 'Reserva al menos un pollo.',
            'quantity.max' => 'Puedes reservar como máximo 100 pollos por pedido.',
            'delivery_date.date' => 'La fecha de entrega no es válida.',
            'note.max' => 'La nota no puede superar los 1000 caracteres.',
            'weights.*.numeric' => 'El peso debe ser un número.',
            'weights.*.min' => 'El peso debe ser mayor a cero.',
            'weights.*.decimal' => 'El peso admite como máximo dos decimales.',
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
