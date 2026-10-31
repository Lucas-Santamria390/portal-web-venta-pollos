@php($user = $user ?? null)
@php($isEdit = $user !== null)
@php($isSelf = $user !== null && auth()->id() === $user->getKey())

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user?->name)" required autofocus maxlength="150" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user?->email)" required maxlength="150" autocomplete="username" />
            <p class="mt-1 text-xs text-slate-400">El usuario entrará al portal con este correo.</p>
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="role" value="Rol" />

            @if ($isSelf)
                <input type="hidden" name="role" value="{{ \App\Enums\UserRole::Admin->value }}">
            @endif

            <select
                id="role"
                name="role"
                @disabled($isSelf)
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:bg-slate-100 disabled:text-slate-500"
            >
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', $user?->role->value ?? \App\Enums\UserRole::Vendedor->value) === $role->value)>
                        {{ $role->label() }}
                    </option>
                @endforeach
            </select>

            <p class="mt-1 text-xs text-slate-400">
                @if ($isSelf)
                    No puedes quitarte el rol de administrador.
                @else
                    El administrador gestiona usuarios y configuración; el vendedor registra pedidos y cobros.
                @endif
            </p>

            <x-input-error class="mt-2" :messages="$errors->get('role')" />
        </div>

        <div>
            <x-input-label for="password" :value="$isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña'" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $isEdit" autocomplete="new-password" />
            <p class="mt-1 text-xs text-slate-400">
                {{ $isEdit ? 'Déjala en blanco para conservar la contraseña actual.' : 'Debe tener al menos 8 caracteres.' }}
            </p>
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="! $isEdit" autocomplete="new-password" />
            <x-input-error class="mt-2" :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="rounded-lg border border-slate-200 p-4">
            @if ($isSelf)
                <input type="hidden" name="active" value="1">
            @endif

            <label for="active" class="flex items-start gap-3">
                <input
                    id="active"
                    name="active"
                    type="checkbox"
                    value="1"
                    @checked(old('active', $user?->active ?? true))
                    @disabled($isSelf)
                    class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500 disabled:opacity-50"
                >

                <span>
                    <span class="block text-sm font-medium text-slate-900">Cuenta activa</span>
                    <span class="mt-1 block text-sm text-slate-500">
                        @if ($isSelf)
                            No puedes desactivar tu propia cuenta.
                        @else
                            Si la desactivas, el usuario no podrá iniciar sesión hasta que la vuelvas a activar.
                        @endif
                    </span>
                </span>
            </label>

            <x-input-error class="mt-2" :messages="$errors->get('active')" />
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
        <button type="submit" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            {{ $submitLabel }}
        </button>

        <a href="{{ $cancelUrl }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
            Cancelar
        </a>
    </div>
</form>
