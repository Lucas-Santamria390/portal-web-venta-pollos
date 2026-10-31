<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'role' => ['required', Rule::enum(UserRole::class)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'active' => ['boolean'],
        ];
    }

    /**
     * @return array<int, Closure>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->route('user');

                if (! $user instanceof User || ! $user->is($this->user())) {
                    return;
                }

                $role = UserRole::tryFrom((string) $this->input('role'));

                if ($role !== null && $role !== UserRole::Admin) {
                    $validator->errors()->add('role', 'No puedes quitarte el rol de administrador.');
                }

                if (! $this->boolean('active')) {
                    $validator->errors()->add('active', 'No puedes desactivar tu propia cuenta.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'active' => $this->boolean('active'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del usuario es obligatorio.',
            'name.max' => 'El nombre no puede superar los 150 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no es válido.',
            'email.max' => 'El correo no puede superar los 150 caracteres.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'role.required' => 'Selecciona un rol.',
            'role.enum' => 'El rol seleccionado no es válido.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'active.boolean' => 'El estado de la cuenta no es válido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'role' => 'rol',
            'password' => 'contraseña',
            'active' => 'estado',
        ];
    }
}
