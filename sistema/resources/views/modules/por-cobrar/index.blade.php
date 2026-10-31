<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Por cobrar</h1>
                <p class="text-sm text-slate-500">Pedidos entregados con saldo pendiente</p>
            </div>

            <a href="{{ route('pedidos.index', ['estado' => 'entregado']) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
                Ver pedidos entregados
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl bg-slate-900 p-5 text-white shadow-sm">
                <p class="text-sm font-medium text-slate-300">Total por cobrar</p>
                <p class="mt-2 text-3xl font-semibold"><x-money :value="$pendingBalance" /></p>
                <p class="mt-1 text-xs text-slate-400">Suma de los saldos de todos los pedidos entregados</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-medium text-slate-500">Pedidos con saldo</p>
                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ $ordersCount }}</p>
                <p class="mt-1 text-xs text-slate-400">Ordenados por fecha de entrega, del más antiguo al más reciente</p>
            </div>
        </div>

        <form method="GET" action="{{ route('por-cobrar.index') }}" class="flex gap-2 sm:max-w-md">
            <input
                type="search"
                name="buscar"
                value="{{ $search }}"
                placeholder="Buscar por cliente"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >

            <button type="submit" class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Buscar
            </button>
        </form>

        @if ($orders->isEmpty())
            <x-empty-state
                title="{{ $search !== '' ? 'No encontramos coincidencias' : 'No hay saldos pendientes' }}"
                description="{{ $search !== '' ? 'No hay pedidos entregados con saldo para ese cliente.' : 'Aquí verás los pedidos entregados que todavía tienen dinero por cobrar.' }}"
            />
        @else
            {{-- Escritorio --}}
            <div class="hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Entregado</th>
                            <th class="px-4 py-3 text-right">Pollos</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Pagado</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($orders as $order)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    @if ($order->customer)
                                        <a href="{{ route('clientes.show', $order->customer) }}" class="font-medium text-slate-900 hover:text-emerald-700">
                                            {{ $order->customer->name }}
                                        </a>
                                    @else
                                        <span class="font-medium text-slate-900">Consumidor final</span>
                                    @endif
                                    <p class="text-xs text-slate-400">
                                        Pedido #{{ $order->getKey() }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                    {{ $order->delivered_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $order->items_count }}</td>
                                <td class="px-4 py-3 text-right text-slate-700"><x-money :value="$order->total" /></td>
                                <td class="px-4 py-3 text-right text-slate-500"><x-money :value="$order->paidAmount()" /></td>
                                <td class="px-4 py-3 text-right font-semibold text-amber-600"><x-money :value="$order->balance()" /></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('pedidos.show', $order) }}" class="text-sm font-medium text-emerald-700 hover:underline">
                                        Cobrar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Celular --}}
            <div class="space-y-3 md:hidden">
                @foreach ($orders as $order)
                    <a href="{{ route('pedidos.show', $order) }}" class="block rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $order->customer?->name ?? 'Consumidor final' }}</p>
                                <p class="text-xs text-slate-400">
                                    Pedido #{{ $order->getKey() }} · entregado {{ $order->delivered_at?->format('d/m/Y') }}
                                </p>
                            </div>

                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Pendiente</span>
                        </div>

                        <div class="mt-3 flex items-end justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Total</p>
                                <p class="text-sm font-medium text-slate-700"><x-money :value="$order->total" /></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Saldo</p>
                                <p class="text-lg font-semibold text-amber-600"><x-money :value="$order->balance()" /></p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div>
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
