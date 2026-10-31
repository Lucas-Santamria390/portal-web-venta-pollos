<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Editar cliente</h1>
            <p class="text-sm text-slate-500">{{ $customer->name }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        @include('modules.clientes.partials.form', [
            'action' => route('clientes.update', $customer),
            'method' => 'PATCH',
            'submitLabel' => 'Guardar cambios',
            'cancelUrl' => route('clientes.show', $customer),
        ])
    </div>
</x-app-layout>
