<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ $customer->name }}</h1>
                <p class="text-sm text-slate-500">Detalle del cliente</p>
            </div>

            <div class="flex items-center gap-2">
                @if (Route::has('pedidos.create'))
                    <a href="{{ route('pedidos.create', ['cliente' => $customer->getKey()]) }}" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Nuevo pedido
                    </a>
                @endif

                <a href="{{ route('clientes.edit', $customer) }}" class="inline-flex rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                    Editar
                </a>
                @if (Auth::user()->isAdmin())
                    <form method="POST" action="{{ route('clientes.destroy', $customer) }}" onsubmit="return confirm('¿Eliminar a {{ addslashes($customer->name) }} y todo su historial de pedidos y cobros? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700">
                            Eliminar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    @php($phoneDigits = $customer->phone ? preg_replace('/\D+/', '', $customer->phone) : null)

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="font-semibold text-slate-900">Contacto</h2>

            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Teléfono</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        @if ($customer->phone)
                            <div class="flex flex-wrap items-center gap-3">
                                <a href="tel:{{ $phoneDigits }}" class="font-medium text-emerald-700 hover:underline">
                                    {{ $customer->phone }}
                                </a>
                                @if ($phoneDigits)
                                    <a
                                        href="https://wa.me/{{ $phoneDigits }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-xs font-medium text-slate-500 hover:text-emerald-700"
                                    >
                                        WhatsApp
                                    </a>
                                @endif
                            </div>
                        @else
                            <span class="text-slate-400">Sin teléfono</span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cliente desde</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $customer->created_at->format('d/m/Y') }}</dd>
                </div>

                <div class="sm:col-span-2">
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Nota</dt>
                    <dd class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $customer->note ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl bg-slate-900 p-5 text-white shadow-sm">
            <p class="text-sm font-medium text-slate-300">Total adeudado</p>
            <p class="mt-2 text-3xl font-semibold"><x-money :value="$pendingBalance" /></p>
            <p class="mt-1 text-xs text-slate-400">
                {{ $openOrdersCount }} {{ $openOrdersCount === 1 ? 'pedido' : 'pedidos' }} con saldo
            </p>

            @if ($pendingBalance > 0)
                <a href="{{ route('por-cobrar.index', ['buscar' => $customer->name]) }}" class="mt-4 inline-flex text-xs font-medium text-emerald-300 hover:underline">
                    Ver en Por cobrar
                </a>
            @endif
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Historial de pedidos</h2>
            <span class="text-xs text-slate-400">{{ $orders->total() }} {{ $orders->total() === 1 ? 'pedido' : 'pedidos' }}</span>
        </div>

        @if ($orders->isEmpty())
            <p class="px-5 py-6 text-sm text-slate-500">Este cliente todavía no tiene pedidos registrados.</p>
        @else
            {{-- Escritorio --}}
            <table class="hidden min-w-full divide-y divide-slate-200 text-sm md:table">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Pedido</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Pagado</th>
                        <th class="px-5 py-3 text-right">Saldo</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($orders as $order)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500">{{ $order->created_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-900">Pedido #{{ $order->getKey() }}</p>
                                <p class="text-xs text-slate-400">{{ $order->items_count }} {{ $order->items_count === 1 ? 'pollo' : 'pollos' }}</p>
                            </td>
                            <td class="px-5 py-3 text-right text-slate-700">
                                @if ($order->isReserved())
                                    <span class="text-slate-400">—</span>
                                @else
                                    <x-money :value="$order->total" />
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right text-slate-500">
                                @if ($order->isReserved())
                                    <span class="text-slate-400">—</span>
                                @else
                                    <x-money :value="$order->paidAmount()" />
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-semibold {{ $order->balance() > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                                @if ($order->isReserved())
                                    <span class="text-slate-400">—</span>
                                @else
                                    <x-money :value="$order->balance()" />
                                @endif
                            </td>
                            <td class="px-5 py-3"><x-order-status :status="$order->status" /></td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('pedidos.show', $order) }}" class="text-sm font-medium text-emerald-700 hover:underline">Ver</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Celular --}}
            <div class="divide-y divide-slate-100 md:hidden">
                @foreach ($orders as $order)
                    <a href="{{ route('pedidos.show', $order) }}" class="block px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">Pedido #{{ $order->getKey() }}</p>
                                <p class="text-xs text-slate-400">{{ $order->created_at->format('d/m/Y') }} · {{ $order->items_count }} {{ $order->items_count === 1 ? 'pollo' : 'pollos' }}</p>
                            </div>
                            <x-order-status :status="$order->status" />
                        </div>

                        <div class="mt-2 flex items-end justify-between">
                            <div>
                                <p class="text-xs text-slate-400">Total</p>
                                <p class="text-sm font-medium text-slate-700">
                                    @if ($order->isReserved())
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <x-money :value="$order->total" />
                                    @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-slate-400">Saldo</p>
                                <p class="text-sm font-semibold {{ $order->balance() > 0 ? 'text-amber-600' : 'text-slate-500' }}">
                                    @if ($order->isReserved())
                                        <span class="text-slate-400">—</span>
                                    @else
                                        <x-money :value="$order->balance()" />
                                    @endif
                                </p>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="px-4 py-3">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
