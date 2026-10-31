<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Pedidos</h1>
                <p class="text-sm text-slate-500">Reservas, entregas y cobros de pollos crudos</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('pedidos.create') }}"
                    class="inline-flex items-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                >
                    Nueva reserva
                </a>

                <a
                    href="{{ route('pedidos.sell') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                >
                    <span class="text-lg leading-none">+</span>
                    Vender ahora
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $tabs = [
            'todas' => 'Todos',
            'reservado' => 'Reservados',
            'entregado' => 'Entregados',
            'cobrado' => 'Cobrados',
            'anulado' => 'Anulados',
        ];
    @endphp

    <div class="space-y-4">
        <div class="flex flex-wrap gap-2">
            @foreach ($tabs as $value => $label)
                <a
                    href="{{ route('pedidos.index', array_filter(['estado' => $value, 'buscar' => $search, 'desde' => $from, 'hasta' => $to])) }}"
                    class="rounded-full px-3 py-1.5 text-sm font-medium transition {{ $status === $value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('pedidos.index') }}" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <input type="hidden" name="estado" value="{{ $status }}">

            <input
                type="search"
                name="buscar"
                value="{{ $search }}"
                placeholder="Buscar por cliente o pedido"
                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >

            <input
                type="date"
                name="desde"
                value="{{ $from }}"
                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >

            <input
                type="date"
                name="hasta"
                value="{{ $to }}"
                class="rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >

            <button type="submit" class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Filtrar
            </button>
        </form>

        @if ($orders->isEmpty())
            <x-empty-state
                title="Aún no hay pedidos"
                description="{{ $search !== '' || $from || $to ? 'No encontramos pedidos con esos filtros.' : 'Registra una reserva o vende ahora para empezar a ver pedidos, entregas y saldos.' }}"
            >
                @if ($search === '' && ! $from && ! $to)
                    <x-slot name="action">
                        <div class="flex flex-wrap justify-center gap-2">
                            <a href="{{ route('pedidos.create') }}" class="inline-flex rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                Nueva reserva
                            </a>
                            <a href="{{ route('pedidos.sell') }}" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Vender ahora
                            </a>
                        </div>
                    </x-slot>
                @endif
            </x-empty-state>
        @else
            {{-- Escritorio --}}
            <div class="hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3 text-right">Pollos</th>
                            <th class="px-4 py-3">Entrega</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Pagado</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                    {{ $order->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('pedidos.show', $order) }}" class="font-medium text-slate-900 hover:text-emerald-700">
                                        {{ $order->customer?->name ?? 'Consumidor final' }}
                                    </a>
                                    <p class="text-xs text-slate-400">Pedido #{{ $order->getKey() }}</p>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $order->items_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                    @if ($order->delivered_at)
                                        {{ $order->delivered_at->format('d/m/Y') }}
                                    @elseif ($order->delivery_date)
                                        <span class="text-xs text-slate-400">Prevista</span> {{ $order->delivery_date->format('d/m/Y') }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-slate-700">
                                    @if ($order->isReserved())
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <x-money :value="$order->total" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-slate-500">
                                    @if ($order->isReserved())
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <x-money :value="$order->paidAmount()" />
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold {{ $order->balance() > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                                    @if ($order->isReserved())
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <x-money :value="$order->balance()" />
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <x-order-status :status="$order->status" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('pedidos.show', $order) }}" class="text-sm font-medium text-emerald-700 hover:underline">Ver</a>
                                        @if (Auth::user()->isAdmin())
                                            <form method="POST" action="{{ route('pedidos.destroy', $order) }}" onsubmit="return confirm('¿Eliminar permanentemente el pedido #{{ $order->getKey() }}?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-sm font-medium text-rose-700 hover:underline">Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Celular --}}
            <div class="space-y-3 md:hidden">
                @foreach ($orders as $order)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <a href="{{ route('pedidos.show', $order) }}" class="block">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $order->customer?->name ?? 'Consumidor final' }}</p>
                                <p class="text-xs text-slate-400">
                                    Pedido #{{ $order->getKey() }} · {{ $order->items_count }} {{ $order->items_count === 1 ? 'pollo' : 'pollos' }}
                                </p>
                            </div>
                            <x-order-status :status="$order->status" />
                        </div>

                        <div class="mt-3 flex items-end justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Total</p>
                                <p class="text-lg font-semibold text-slate-900">
                                    @if ($order->isReserved())
                                        <span class="text-sm font-normal text-slate-400">Se calcula al entregar</span>
                                    @else
                                        <x-money :value="$order->total" />
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Saldo</p>
                                <p class="text-lg font-semibold {{ $order->balance() > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                                    @if ($order->isReserved())
                                        <span class="text-sm font-normal text-slate-400">—</span>
                                    @else
                                        <x-money :value="$order->balance()" />
                                    @endif
                                </p>
                            </div>
                        </div>
                        </a>
                        @if (Auth::user()->isAdmin())
                            <form method="POST" action="{{ route('pedidos.destroy', $order) }}" class="mt-3" onsubmit="return confirm('¿Eliminar permanentemente el pedido #{{ $order->getKey() }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-rose-700">Eliminar pedido</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <div>
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
