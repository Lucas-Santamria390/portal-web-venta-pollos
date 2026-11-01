@php
    $autoPrice = (bool) $setting->auto_price;
    $initialPricePerLb = old('price_per_lb', (float) $order->price_per_lb > 0 ? $order->price_per_lb : $setting->price_per_lb);
    $canDeriveWeight = ! $autoPrice && (float) $initialPricePerLb > 0;
    $deliveryWeights = $order->items->values()->map(
        fn ($item, $index) => old("weights.{$index}", $item->weight_lb !== null ? number_format((float) $item->weight_lb, 2, '.', '') : '')
    )->all();
    $deliveryPrices = $order->items->values()->map(
        fn ($item, $index) => old("prices.{$index}", $item->price !== null ? number_format((float) $item->price, 2, '.', '') : '')
    )->all();
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-semibold text-slate-900">Entregar pedido #{{ $order->getKey() }}</h1>
                <x-order-status :status="$order->status" />
            </div>

            <a href="{{ route('pedidos.show', $order) }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
                Volver al pedido
            </a>
        </div>
    </x-slot>

    <form
        method="POST"
        action="{{ route('pedidos.deliver', $order) }}"
        x-data="deliveryForm({
            autoPrice: @js($autoPrice),
            pricePerLb: @js((float) $initialPricePerLb),
            weights: {{ Js::from($deliveryWeights) }},
            prices: {{ Js::from($deliveryPrices) }}
        })"
        class="space-y-6"
    >
        @csrf

        @if ($errors->any())
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <p class="font-semibold">Revisa los datos de la entrega:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Datos del pedido</h2>

                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cliente</dt>
                            <dd class="mt-1 text-sm font-medium text-slate-900">
                                {{ $order->customer?->name ?? 'Consumidor final' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Pollos</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $order->items->count() }}</dd>
                        </div>

                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Reservado</dt>
                            <dd class="mt-1 text-sm text-slate-900">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                        </div>

                        @if ($order->delivery_date)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Entrega prevista</dt>
                                <dd class="mt-1 text-sm text-slate-900">{{ $order->delivery_date->format('d/m/Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-semibold text-slate-900">Peso y precio de cada pollo</h2>
                        <span class="text-xs text-slate-400">
                            {{ $canDeriveWeight ? 'El peso se calcula desde el precio y puede ajustarse' : 'El peso es obligatorio para entregar' }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach ($order->items as $index => $item)
                            <div class="rounded-lg border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-900">Pollo {{ $index + 1 }}</p>
                                    <p class="text-sm font-semibold text-slate-900" x-text="money(chickenPrice({{ $index }}))"></p>
                                </div>

                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    @unless ($autoPrice)
                                        <div>
                                            <label class="text-xs font-medium text-slate-500">Precio del pollo</label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0.01"
                                                max="9999999999.99"
                                                name="prices[{{ $index }}]"
                                                value="{{ old("prices.{$index}", $item->price !== null ? number_format((float) $item->price, 2, '.', '') : '') }}"
                                                x-model="prices[{{ $index }}]"
                                                x-on:input="syncWeightFromPrice({{ $index }})"
                                                required
                                                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            >
                                        </div>
                                    @endunless

                                    <div>
                                        <label class="text-xs font-medium text-slate-500">
                                            Peso en libras
                                            @if ($canDeriveWeight)
                                                <span class="font-normal text-slate-400">(calculado, editable)</span>
                                            @endif
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max="9999.99"
                                            name="weights[{{ $index }}]"
                                            value="{{ old("weights.{$index}", $item->weight_lb !== null ? number_format((float) $item->weight_lb, 2, '.', '') : '') }}"
                                            x-model="weights[{{ $index }}]"
                                            @if (! $canDeriveWeight) required @endif
                                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                        @if ($canDeriveWeight)
                                            <p class="mt-1 text-xs text-slate-400">
                                                Se calcula con el precio por libra de referencia: <x-money :value="$initialPricePerLb" />.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <aside>
                <div class="rounded-xl bg-slate-900 p-5 text-white shadow-sm lg:sticky lg:top-6">
                    @if ($autoPrice)
                        <label for="price_per_lb" class="text-sm font-medium text-slate-300">Precio por libra</label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">$</span>
                            <input
                                id="price_per_lb"
                                name="price_per_lb"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max="999999.99"
                                x-model.number="pricePerLb"
                                required
                                class="block w-full rounded-lg border-slate-700 bg-white/10 pl-7 text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                            >
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Puedes ajustarlo para este pedido.</p>
                    @else
                        <p class="text-sm font-medium text-slate-300">Precio manual</p>
                        <p class="mt-1 text-xs text-slate-400">
                            El precio automático está desactivado. Escribe el precio de cada pollo; el peso se calcula automáticamente con el precio por libra de referencia y puedes ajustarlo.
                        </p>
                    @endif

                    <div class="mt-5 border-t border-white/10 pt-4">
                        <p class="text-sm font-medium text-slate-300">Total del pedido</p>
                        <p class="mt-1 text-3xl font-semibold" x-text="money(total)"></p>
                    </div>

                    <button
                        type="submit"
                        class="mt-6 w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-emerald-400"
                    >
                        Confirmar entrega
                    </button>

                    <p class="mt-3 text-xs text-slate-400">
                        Al confirmar, el pedido pasa a entregado y queda por cobrar el saldo.
                    </p>
                </div>
            </aside>
        </div>
    </form>
</x-app-layout>
