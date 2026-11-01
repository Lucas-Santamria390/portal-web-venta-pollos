@php
    $isSell = $mode === 'sell';
    $isEdit = $mode === 'edit';
    $isDeliveredEdit = $isEdit && $order?->isDelivered();
    $showPricing = $isSell || $isDeliveredEdit;
    $autoPrice = (bool) $setting->auto_price;

    $initialQuantity = (int) old('quantity', $order?->items->count() ?: 1);
    $initialWeights = old('weights', $order?->items->map(fn ($item) => $item->weight_lb !== null ? number_format((float) $item->weight_lb, 2, '.', '') : '')->values()->all() ?? []);
    $initialPrices = old('prices', $order?->items->map(fn ($item) => $item->price !== null ? number_format((float) $item->price, 2, '.', '') : '')->values()->all() ?? []);
    $initialPricePerLb = old('price_per_lb', (float) ($order?->price_per_lb ?? 0) > 0 ? $order->price_per_lb : $setting->price_per_lb);
    $canDeriveWeight = $showPricing && ! $autoPrice && (float) $initialPricePerLb > 0;
    $requireWeights = ($isSell || $isDeliveredEdit) && ! $canDeriveWeight;
    $initialCustomerId = old('customer_id', $order?->customer_id ?? $selectedCustomerId ?? '');
    $initialDeliveryDate = old('delivery_date', $order?->delivery_date?->format('Y-m-d') ?? '');
    $initialNote = old('note', $order?->note ?? '');
    $initialPaid = old('paid_amount', '');
    $initialMethod = old('method', \App\Enums\PaymentMethod::Efectivo->value);

    $title = match ($mode) {
        'sell' => 'Vender ahora',
        'edit' => 'Editar pedido',
        default => 'Nueva reserva',
    };

    $submitLabel = match ($mode) {
        'sell' => 'Vender ahora',
        'edit' => 'Guardar cambios',
        default => 'Guardar reserva',
    };
@endphp

<form
    method="POST"
    action="{{ $action }}"
    x-data="orderForm({
        mode: @js($mode),
        customers: {{ Js::from($customers) }},
        csrf: @js(csrf_token()),
        autoPrice: @js($autoPrice),
        pricePerLb: @js((float) $setting->price_per_lb),
        initialQuantity: @js($initialQuantity),
        initialWeights: {{ Js::from($initialWeights) }},
        initialPrices: {{ Js::from($initialPrices) }},
        initialCustomerId: @js((string) $initialCustomerId),
        initialDeliveryDate: @js((string) $initialDeliveryDate),
        initialNote: @js((string) $initialNote),
        initialPricePerLb: @js((float) $initialPricePerLb),
        initialPaidAmount: @js((string) $initialPaid),
        initialMethod: @js((string) $initialMethod)
    })"
    class="space-y-6"
>
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Revisa los datos del pedido:</p>
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
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold text-slate-900">Cliente</h2>
                    <button
                        type="button"
                        x-on:click="quickCreate = true"
                        class="text-sm font-medium text-emerald-700 hover:underline"
                    >
                        + Crear cliente
                    </button>
                </div>

                <div class="mt-3">
                    <select
                        id="customer_id"
                        name="customer_id"
                        x-model="customerId"
                        @if (! $isSell) required @endif
                        class="block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                        <option value="">{{ $isSell ? 'Consumidor final (pago completo)' : 'Seleccionar cliente…' }}</option>
                        <template x-for="customer in customers" :key="customer.id">
                            <option
                                :value="String(customer.id)"
                                x-text="customer.name + (customer.phone ? ' · ' + customer.phone : '')"
                            ></option>
                        </template>
                    </select>

                    @if ($isSell)
                        <p
                            x-show="isFiado && ! customerId"
                            x-cloak
                            class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700"
                        >
                            Si el pago no cubre el total, selecciona un cliente para registrar el fiado.
                        </p>
                    @endif
                </div>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-semibold text-slate-900">Pollos</h2>
                    <span class="text-xs text-slate-400" x-text="quantity + (quantity === 1 ? ' pollo' : ' pollos')"></span>
                </div>

                <div class="mt-4 sm:max-w-xs">
                    <label for="quantity" class="text-sm font-medium text-slate-700">Cantidad de pollos</label>
                    <input
                        id="quantity"
                        name="quantity"
                        type="number"
                        min="1"
                        max="100"
                        step="1"
                        x-model.number="quantity"
                        x-on:change="resizeRows()"
                        required
                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>

                <p class="mt-3 text-xs text-slate-400">
                    @if ($requireWeights)
                        El peso de cada pollo es obligatorio para calcular el precio.
                    @elseif ($showPricing && ! $autoPrice)
                        Escribe el precio de cada pollo y el peso se calculará automáticamente. Puedes ajustarlo.
                    @else
                        Puedes dejar los pesos para el momento de la entrega.
                    @endif
                </p>

                <div class="mt-4 space-y-3">
                    <template x-for="(weight, index) in weights" :key="index">
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-900" x-text="'Pollo ' + (index + 1)"></p>

                                @if ($showPricing)
                                    <p class="text-sm font-semibold text-slate-900" x-text="money(chickenPrice(index))"></p>
                                @else
                                    <p class="text-xs text-slate-400">El precio se calcula al entregar</p>
                                @endif
                            </div>

                            <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                @if ($showPricing && ! $autoPrice)
                                    <div>
                                        <label class="text-xs font-medium text-slate-500">Precio del pollo</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            max="9999999999.99"
                                            x-bind:name="'prices[' + index + ']'"
                                            x-model="prices[index]"
                                            x-on:input="syncWeightFromPrice(index); syncPaid()"
                                            required
                                            class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                        >
                                    </div>
                                @endif

                                <div>
                                    <label class="text-xs font-medium text-slate-500">
                                        Peso en libras
                                        @if ($canDeriveWeight)
                                            <span class="font-normal text-slate-400">(calculado, editable)</span>
                                        @elseif (! $requireWeights)
                                            <span class="font-normal text-slate-400">(opcional)</span>
                                        @endif
                                    </label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        max="9999.99"
                                        x-bind:name="'weights[' + index + ']'"
                                        x-model="weights[index]"
                                        x-on:input="syncPaid()"
                                        @if ($requireWeights) required @endif
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
                    </template>
                </div>
            </div>

            @unless ($isSell)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <label for="delivery_date" class="text-sm font-medium text-slate-700">Fecha de entrega (opcional)</label>
                    <input
                        id="delivery_date"
                        name="delivery_date"
                        type="date"
                        x-model="deliveryDate"
                        class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:max-w-xs"
                    >
                </div>
            @endunless

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <label for="note" class="text-sm font-medium text-slate-700">Nota (opcional)</label>
                <textarea
                    id="note"
                    name="note"
                    rows="3"
                    maxlength="1000"
                    x-model="note"
                    class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                ></textarea>
            </div>
        </div>

        <aside>
            <div class="rounded-xl bg-slate-900 p-5 text-white shadow-sm lg:sticky lg:top-6">
                @if ($showPricing)
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
                                x-on:input="syncPaid()"
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
                        <p class="text-sm font-medium text-slate-300">Total</p>
                        <p class="mt-1 text-3xl font-semibold" x-text="money(total)"></p>
                    </div>

                    @if ($isSell)
                        <div class="mt-5 border-t border-white/10 pt-4">
                            <label for="paid_amount" class="text-sm font-medium text-slate-300">Pago recibido</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-400">$</span>
                                <input
                                    id="paid_amount"
                                    name="paid_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="9999999999.99"
                                    x-model.number="paidAmount"
                                    x-on:input="paidTouched = true"
                                    required
                                    class="block w-full rounded-lg border-slate-700 bg-white/10 pl-7 text-white placeholder-slate-400 focus:border-emerald-500 focus:ring-emerald-500"
                                >
                            </div>

                            <label for="method" class="mt-3 block text-sm font-medium text-slate-300">Medio de pago</label>
                            <select
                                id="method"
                                name="method"
                                x-model="method"
                                class="mt-2 block w-full rounded-lg border-slate-700 bg-white/10 text-white focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                @foreach (\App\Enums\PaymentMethod::cases() as $paymentMethod)
                                    <option value="{{ $paymentMethod->value }}" class="text-slate-900">{{ $paymentMethod->label() }}</option>
                                @endforeach
                            </select>

                            <div class="mt-4 flex items-center justify-between text-sm">
                                <span class="text-slate-300">Saldo pendiente</span>
                                <span class="font-semibold" x-text="money(balance)"></span>
                            </div>

                            <p x-show="isFiado" x-cloak class="mt-2 text-xs text-amber-300">
                                Quedará como fiado. El cliente es obligatorio.
                            </p>
                        </div>
                    @endif
                @else
                    <p class="text-sm font-medium text-slate-300">Reserva</p>
                    <p class="mt-2 text-3xl font-semibold" x-text="quantity + (quantity === 1 ? ' pollo' : ' pollos')"></p>
                    <p class="mt-2 text-sm text-slate-400">
                        Precio actual por libra: <span class="font-medium text-white"><x-money :value="$setting->price_per_lb" /> / lb</span>
                    </p>
                    <p class="mt-3 text-xs text-slate-400">
                        El total se calcula al confirmar la entrega, cuando se registran los pesos.
                    </p>
                @endif

                <button
                    type="submit"
                    class="mt-6 w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-emerald-400"
                >
                    {{ $submitLabel }}
                </button>

                <a
                    href="{{ $order ? route('pedidos.show', $order) : route('pedidos.index') }}"
                    class="mt-2 block w-full rounded-lg bg-white/10 px-4 py-2.5 text-center text-sm font-semibold text-white ring-1 ring-white/20 transition hover:bg-white/20"
                >
                    Cancelar
                </a>
            </div>
        </aside>
    </div>

    <div x-show="quickCreate" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="quickCreate = false"></div>

        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-slate-900">Nuevo cliente</h2>
            <p class="mt-1 text-sm text-slate-500">Se agrega a la lista y queda seleccionado.</p>

            <p
                x-show="customerError"
                x-text="customerError"
                class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700"
            ></p>

            <div class="mt-4 space-y-3">
                <div>
                    <label for="quick_customer_name" class="text-sm font-medium text-slate-700">Nombre</label>
                    <input
                        id="quick_customer_name"
                        type="text"
                        x-model="newCustomer.name"
                        maxlength="150"
                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>

                <div>
                    <label for="quick_customer_phone" class="text-sm font-medium text-slate-700">Teléfono (opcional)</label>
                    <input
                        id="quick_customer_phone"
                        type="text"
                        x-model="newCustomer.phone"
                        maxlength="30"
                        class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                    >
                </div>
            </div>

            <div class="mt-5 flex justify-end gap-3">
                <button type="button" x-on:click="quickCreate = false" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700">
                    Cancelar
                </button>
                <button
                    type="button"
                    x-on:click="createCustomer"
                    x-bind:disabled="savingCustomer"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                    x-text="savingCustomer ? 'Guardando…' : 'Crear cliente'"
                ></button>
            </div>
        </div>
    </div>
</form>
