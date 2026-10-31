<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Inicio</h1>
                <p class="text-sm text-slate-500">Resumen de reservas, entregas y cobros</p>
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

    <div class="space-y-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <a
                href="{{ route('pedidos.index', ['estado' => 'reservado']) }}"
                class="rounded-xl bg-slate-900 p-5 text-white shadow-sm transition hover:bg-slate-800"
            >
                <p class="text-sm font-medium text-slate-300">Reservas pendientes</p>
                <p class="mt-2 text-3xl font-semibold">{{ $pendingReservations }}</p>
                <p class="mt-1 text-xs text-slate-400">
                    {{ $pendingReservations === 1 ? 'reserva por entregar' : 'reservas por entregar' }}
                </p>
            </a>

            <a
                href="{{ route('pedidos.index', ['estado' => 'reservado']) }}"
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50"
            >
                <p class="text-sm font-medium text-slate-500">Pollos reservados</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $chickensReserved }}</p>
                <p class="mt-1 text-xs text-slate-400">
                    {{ $chickensReserved === 1 ? 'pollo comprometido' : 'pollos comprometidos' }}
                </p>
            </a>

            <a
                href="{{ route('por-cobrar.index') }}"
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50"
            >
                <p class="text-sm font-medium text-slate-500">Entregados sin cobrar</p>
                <p class="mt-2 text-3xl font-semibold {{ $deliveredUnpaid > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                    {{ $deliveredUnpaid }}
                </p>
                <p class="mt-1 text-xs text-slate-400">
                    Saldo: <span class="font-medium text-slate-600"><x-money :value="$pendingBalance" /></span>
                </p>
            </a>

            <a
                href="{{ route('pedidos.index', ['desde' => now()->startOfMonth()->format('Y-m-d'), 'hasta' => now()->endOfMonth()->format('Y-m-d')]) }}"
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50"
            >
                <p class="text-sm font-medium text-slate-500">Cobrado este mes</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900"><x-money :value="$collectedThisMonth" /></p>
                <p class="mt-1 text-xs text-slate-400">Pagos registrados en el mes</p>
            </a>

            <a
                href="{{ route('pedidos.index', ['desde' => now()->startOfMonth()->format('Y-m-d'), 'hasta' => now()->endOfMonth()->format('Y-m-d')]) }}"
                class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50"
            >
                <p class="text-sm font-medium text-slate-500">Libras vendidas este mes</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ number_format($poundsThisMonth, 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-400">Peso de los pollos entregados</p>
            </a>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Próximas reservas</h2>
                    <a href="{{ route('pedidos.index', ['estado' => 'reservado']) }}" class="text-sm font-medium text-emerald-700 hover:underline">
                        Ver reservas
                    </a>
                </div>

                @if ($upcomingReservations->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500">No hay reservas pendientes.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($upcomingReservations as $order)
                            <li>
                                <a href="{{ route('pedidos.show', $order) }}" class="block px-5 py-3 transition hover:bg-slate-50">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-900">
                                                {{ $order->customer?->name ?? 'Consumidor final' }}
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                Pedido #{{ $order->getKey() }} · {{ $order->items_count }} {{ $order->items_count === 1 ? 'pollo' : 'pollos' }}
                                            </p>
                                        </div>

                                        <div class="shrink-0 text-right">
                                            @if ($order->delivery_date)
                                                <p class="text-sm font-medium text-slate-700">{{ $order->delivery_date->format('d/m/Y') }}</p>
                                                <p class="text-xs text-slate-400">Entrega prevista</p>
                                            @else
                                                <p class="text-sm text-slate-400">Sin fecha</p>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Pedidos recientes</h2>
                    <a href="{{ route('pedidos.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">
                        Ver todos
                    </a>
                </div>

                @if ($recentOrders->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500">Aún no hay pedidos registrados.</p>
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($recentOrders as $order)
                            <li>
                                <a href="{{ route('pedidos.show', $order) }}" class="block px-5 py-3 transition hover:bg-slate-50">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-900">
                                                {{ $order->customer?->name ?? 'Consumidor final' }}
                                            </p>
                                            <p class="text-xs text-slate-400">
                                                Pedido #{{ $order->getKey() }} · {{ $order->created_at->format('d/m/Y H:i') }}
                                            </p>
                                        </div>

                                        <x-order-status :status="$order->status" />
                                    </div>

                                    <div class="mt-2 flex items-end justify-between">
                                        <p class="text-sm text-slate-500">
                                            {{ $order->items_count }} {{ $order->items_count === 1 ? 'pollo' : 'pollos' }}
                                        </p>

                                        @unless ($order->isReserved() || $order->isCancelled())
                                            <p class="text-sm {{ $order->balance() > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                                                Saldo <span class="font-semibold"><x-money :value="$order->balance()" /></span>
                                            </p>
                                        @endunless
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
