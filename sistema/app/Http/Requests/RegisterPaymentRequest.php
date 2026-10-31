<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterPaymentRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99', 'decimal:0,2'],
            'method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'paid_at' => ['nullable', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'note' => filled($this->input('note')) ? trim((string) $this->input('note')) : null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return route('pedidos.show', $this->route('order'));
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a cero.',
            'amount.decimal' => 'El monto admite como máximo dos decimales.',
            'method.required' => 'Selecciona un medio de pago.',
            'method.in' => 'Selecciona un medio de pago válido.',
            'paid_at.date' => 'La fecha de pago no es válida.',
            'paid_at.before_or_equal' => 'La fecha de pago no puede ser futura.',
            'note.max' => 'La nota no puede superar los 255 caracteres.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'monto',
            'method' => 'medio de pago',
            'paid_at' => 'fecha de pago',
            'note' => 'nota',
        ];
    }
}
