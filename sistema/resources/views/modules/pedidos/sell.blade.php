<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Vender ahora</h1>
                <p class="text-sm text-slate-500">Registra la venta, entrega y cobro en un solo paso</p>
            </div>

            <a href="{{ route('pedidos.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
                Volver a pedidos
            </a>
        </div>
    </x-slot>

    @include('modules.pedidos.partials.form', [
        'action' => route('pedidos.sell.store'),
        'method' => 'POST',
        'mode' => 'sell',
        'customers' => $customers,
        'setting' => $setting,
        'order' => null,
        'selectedCustomerId' => $selectedCustomerId,
    ])
</x-app-layout>
