@php
    $periods = [
        'hoy' => 'Hoy',
        'semana' => 'Esta semana',
        'mes' => 'Este mes',
        'todo' => 'Todo',
    ];

    $rangeLabel = match ($summary['period']) {
        'hoy' => now()->translatedFormat('d \d\e F \d\e Y'),
        'semana' => $summary['from']->translatedFormat('d M').' – '.$summary['to']->translatedFormat('d M Y'),
        'todo' => 'Historial completo',
        default => now()->translatedFormat('F \d\e Y'),
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Dashboard</h1>
                <p class="text-sm text-slate-500">Ventas, cobros y saldos del negocio</p>
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
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                @foreach ($periods as $value => $label)
                    <a
                        href="{{ route('dashboard.panel', ['periodo' => $value]) }}"
                        class="rounded-full px-3 py-1.5 text-sm font-medium transition {{ $summary['period'] === $value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <p class="text-xs text-slate-500">Periodo: {{ $rangeLabel }}</p>
        </div>

        {{-- Indicadores principales --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Cobrado</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700"><x-money :value="$summary['collected']" /></p>
                <p class="mt-1 text-xs text-slate-500">{{ $summary['paymentsCount'] }} {{ $summary['paymentsCount'] === 1 ? 'cobro registrado' : 'cobros registrados' }}</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Vendido</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900"><x-money :value="$summary['soldTotal']" /></p>
                <p class="mt-1 text-xs text-slate-500">{{ $summary['soldOrdersCount'] }} {{ $summary['soldOrdersCount'] === 1 ? 'pedido entregado' : 'pedidos entregados' }}</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Pollos vendidos</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['chickensSold'], 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Unidades entregadas en el periodo</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Libras vendidas</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($summary['poundsSold'], 2, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Peso total entregado</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Saldo por cobrar</p>
                <p class="mt-2 text-2xl font-semibold text-amber-600"><x-money :value="$summary['pendingBalance']" /></p>
                <p class="mt-1 text-xs text-slate-500">{{ $summary['deliveredUnpaid'] }} {{ $summary['deliveredUnpaid'] === 1 ? 'pedido pendiente' : 'pedidos pendientes' }} · estado actual</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Cobros por medio de pago --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">Cobros por medio de pago</h2>
                        <p class="text-xs text-slate-500">{{ $summary['periodLabel'] }} · {{ $summary['paymentsCount'] }} cobros</p>
                    </div>
                    <a href="{{ route('pedidos.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">Ver pedidos</a>
                </div>

                <div class="space-y-4 px-5 py-4">
                    @foreach ($summary['methods'] as $method)
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <x-payment-method :method="$method['method']" />
                                    <span class="text-xs text-slate-500">{{ $method['count'] }} {{ $method['count'] === 1 ? 'cobro' : 'cobros' }}</span>
                                </div>

                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold text-slate-900"><x-money :value="$method['total']" /></span>
                                    <span class="w-12 text-right text-xs font-medium text-slate-500">{{ number_format($method['percentage'], 1, ',', '.') }}%</span>
                                    <a
                                        href="{{ route('pedidos.index', ['metodo' => $method['method']->value]) }}"
                                        class="text-xs font-medium text-emerald-700 hover:underline"
                                    >
                                        Ver
                                    </a>
                                </div>
                            </div>

                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-emerald-500"
                                    style="width: {{ max($method['percentage'], 0) }}%"
                                ></div>
                            </div>
                        </div>
                    @endforeach

                    @if ($summary['collected'] <= 0)
                        <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            No hay cobros registrados en este periodo.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Resumen complementario --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="font-semibold text-slate-900">Resumen del periodo</h2>
                    <p class="text-xs text-slate-500">Promedios y estado de reservas</p>
                </div>

                <dl class="divide-y divide-slate-100">
                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-sm text-slate-600">Ticket promedio</dt>
                        <dd class="text-sm font-semibold text-slate-900"><x-money :value="$summary['averageTicket']" /></dd>
                    </div>

                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-sm text-slate-600">Precio promedio por libra</dt>
                        <dd class="text-sm font-semibold text-slate-900"><x-money :value="$summary['averagePricePerLb']" /></dd>
                    </div>

                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-sm text-slate-600">Reservas pendientes</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $summary['pendingReservations'] }}</dd>
                    </div>

                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-sm text-slate-600">Pollos reservados</dt>
                        <dd class="text-sm font-semibold text-slate-900">{{ $summary['chickensReserved'] }}</dd>
                    </div>

                    <div class="flex items-center justify-between px-5 py-3">
                        <dt class="text-sm text-slate-600">Entregados sin cobrar</dt>
                        <dd class="text-sm font-semibold text-amber-600">{{ $summary['deliveredUnpaid'] }}</dd>
                    </div>
                </dl>

                <p class="border-t border-slate-100 px-5 py-3 text-[11px] leading-relaxed text-slate-400">
                    “Vendido” cuenta pedidos entregados en el periodo según su fecha de entrega.
                    “Cobrado” cuenta pagos registrados en el periodo.
                </p>
            </div>
        </div>

        {{-- Últimos 7 días --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Cobros de los últimos 7 días</h2>
                    <p class="text-xs text-slate-500">Independiente del periodo seleccionado</p>
                </div>
                <p class="text-sm font-semibold text-slate-900">
                    <x-money :value="$summary['last7Days']->sum('total')" />
                </p>
            </div>

            <div class="px-5 py-5">
                <div class="flex items-end gap-2 sm:gap-4">
                    @foreach ($summary['last7Days'] as $day)
                        @php
                            $height = $day['total'] > 0
                                ? max(6, round($day['total'] / $summary['chartMax'] * 100, 1))
                                : 2;
                        @endphp

                        <div class="flex flex-1 flex-col items-center gap-2">
                            <div class="flex h-32 w-full items-end">
                                <div
                                    class="w-full rounded-t-md bg-emerald-500/90 transition hover:bg-emerald-600"
                                    style="height: {{ $height }}%"
                                    title="{{ $day['label'] }}: ${{ number_format($day['total'], 2, ',', '.') }}"
                                ></div>
                            </div>
                            <span class="text-[11px] font-medium text-slate-500">{{ $day['label'] }}</span>
                            <span class="text-[10px] text-slate-400"><x-money :value="$day['total']" /></span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Actividad reciente --}}
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">Últimos cobros</h2>
                        <p class="text-xs text-slate-500">Pagos registrados recientemente</p>
                    </div>
                    <a href="{{ route('por-cobrar.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">Por cobrar</a>
                </div>

                @if ($summary['recentPayments']->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500">Todavía no hay cobros registrados.</p>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($summary['recentPayments'] as $payment)
                            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('pedidos.show', $payment->order_id) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-emerald-700">
                                        {{ $payment->order?->customer?->name ?? 'Consumidor final' }}
                                    </a>
                                    <p class="text-xs text-slate-400">
                                        Pedido #{{ $payment->order_id }} · {{ $payment->paid_at?->format('d/m/Y H:i') }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <x-payment-method :method="$payment->method" />
                                    <span class="text-sm font-semibold text-emerald-700"><x-money :value="$payment->amount" /></span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="font-semibold text-slate-900">Pedidos recientes</h2>
                        <p class="text-xs text-slate-500">Últimos movimientos del negocio</p>
                    </div>
                    <a href="{{ route('pedidos.index') }}" class="text-sm font-medium text-emerald-700 hover:underline">Ver todos</a>
                </div>

                @if ($summary['recentOrders']->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500">Aún no hay pedidos registrados.</p>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($summary['recentOrders'] as $order)
                            <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('pedidos.show', $order) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-emerald-700">
                                        {{ $order->customer?->name ?? 'Consumidor final' }}
                                    </a>
                                    <p class="text-xs text-slate-400">
                                        Pedido #{{ $order->getKey() }} · {{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->items_count }} {{ $order->items_count === 1 ? 'pollo' : 'pollos' }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <x-order-status :status="$order->status" />
                                    <span class="w-24 text-right text-sm font-semibold text-slate-900">
                                        @if ($order->isReserved())
                                            <span class="text-xs font-normal text-slate-400">Sin total</span>
                                        @else
                                            <x-money :value="$order->total" />
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Próximas reservas --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">Próximas reservas</h2>
                    <p class="text-xs text-slate-500">Pedidos reservados por fecha de entrega</p>
                </div>
                <a href="{{ route('pedidos.index', ['estado' => 'reservado']) }}" class="text-sm font-medium text-emerald-700 hover:underline">Ver reservas</a>
            </div>

            @if ($summary['upcomingReservations']->isEmpty())
                <p class="px-5 py-6 text-sm text-slate-500">No hay reservas pendientes.</p>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($summary['upcomingReservations'] as $order)
                        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3">
                            <div class="min-w-0">
                                <a href="{{ route('pedidos.show', $order) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-emerald-700">
                                    {{ $order->customer?->name ?? 'Consumidor final' }}
                                </a>
                                <p class="text-xs text-slate-400">
                                    Pedido #{{ $order->getKey() }} · {{ $order->items_count }} {{ $order->items_count === 1 ? 'pollo' : 'pollos' }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="text-xs text-slate-500">
                                    {{ $order->delivery_date?->format('d/m/Y') ?? 'Sin fecha' }}
                                </span>
                                <a href="{{ route('pedidos.show', $order) }}" class="text-sm font-medium text-emerald-700 hover:underline">Ver</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
