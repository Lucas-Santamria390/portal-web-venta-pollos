<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-semibold text-slate-900">Editar pedido #{{ $order->getKey() }}</h1>
                <x-order-status :status="$order->status" />
            </div>

            <a href="{{ route('pedidos.show', $order) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
                Volver al pedido
            </a>
        </div>
    </x-slot>

    @include('modules.pedidos.partials.form', [
        'action' => route('pedidos.update', $order),
        'method' => 'PATCH',
        'mode' => 'edit',
        'customers' => $customers,
        'setting' => $setting,
        'order' => $order,
        'selectedCustomerId' => null,
    ])
</x-app-layout>
