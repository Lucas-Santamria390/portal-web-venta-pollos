<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Nuevo usuario</h1>
            <p class="text-sm text-slate-500">Cuenta y permisos del personal</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        @include('modules.usuarios.partials.form', [
            'action' => route('usuarios.store'),
            'method' => 'POST',
            'submitLabel' => 'Crear usuario',
            'cancelUrl' => route('usuarios.index'),
        ])
    </div>
</x-app-layout>
