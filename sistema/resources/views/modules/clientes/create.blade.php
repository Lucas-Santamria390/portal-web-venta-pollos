<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Nuevo cliente</h1>
            <p class="text-sm text-slate-500">Nombre y datos de contacto</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-2xl rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-6">
        @include('modules.clientes.partials.form', [
            'action' => route('clientes.store'),
            'method' => 'POST',
            'submitLabel' => 'Guardar cliente',
            'cancelUrl' => route('clientes.index'),
        ])
    </div>
</x-app-layout>
