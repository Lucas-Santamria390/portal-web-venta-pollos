<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Clientes</h1>
                <p class="text-sm text-slate-500">Datos de contacto y saldo pendiente</p>
            </div>

            <a
                href="{{ route('clientes.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
            >
                <span class="text-lg leading-none">+</span>
                Nuevo cliente
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" action="{{ route('clientes.index') }}" class="flex gap-2 sm:max-w-md">
            <input
                type="search"
                name="buscar"
                value="{{ $search }}"
                placeholder="Buscar por nombre o teléfono"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >

            <button type="submit" class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Buscar
            </button>
        </form>

        @if ($customers->isEmpty())
            <x-empty-state
                title="No hay clientes para mostrar"
                description="{{ $search !== '' ? 'No encontramos clientes con ese criterio de búsqueda.' : 'Registra a tus clientes con nombre y teléfono para asociarlos a sus pedidos.' }}"
            >
                @if ($search === '')
                    <x-slot name="action">
                        <a href="{{ route('clientes.create') }}" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Registrar el primer cliente
                        </a>
                    </x-slot>
                @endif
            </x-empty-state>
        @else
            {{-- Escritorio --}}
            <div class="hidden overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 md:block">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Teléfono</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3">Último pedido</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($customers as $customer)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a href="{{ route('clientes.show', $customer) }}" class="font-medium text-slate-900 hover:text-emerald-700">
                                        {{ $customer->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ $customer->phone ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium {{ (float) $customer->open_debt > 0 ? 'text-amber-600' : 'text-slate-700' }}">
                                    <x-money :value="$customer->open_debt ?? 0" />
                                </td>
                                <td class="px-4 py-3 text-slate-500">
                                    {{ $customer->last_purchase_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-3">
                                        <a href="{{ route('clientes.show', $customer) }}" class="text-sm font-medium text-emerald-700 hover:underline">Ver</a>
                                        @if (Auth::user()->isAdmin())
                                            <form method="POST" action="{{ route('clientes.destroy', $customer) }}" onsubmit="return confirm('¿Eliminar a {{ addslashes($customer->name) }} y todo su historial de pedidos y cobros?')">
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
                @foreach ($customers as $customer)
                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <a href="{{ route('clientes.show', $customer) }}" class="block">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">{{ $customer->name }}</p>
                                <p class="text-sm text-slate-500">{{ $customer->phone ?? 'Sin teléfono' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold {{ (float) $customer->open_debt > 0 ? 'text-amber-600' : 'text-slate-900' }}">
                                    <x-money :value="$customer->open_debt ?? 0" />
                                </p>
                                <p class="text-xs text-slate-400">Saldo</p>
                            </div>
                        </div>
                        </a>
                        @if (Auth::user()->isAdmin())
                            <form method="POST" action="{{ route('clientes.destroy', $customer) }}" class="mt-3" onsubmit="return confirm('¿Eliminar este cliente y todo su historial de pedidos y cobros?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-rose-700">Eliminar cliente</button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <div>
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
