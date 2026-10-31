@php
    $canEdit = $order->canBeEditedBy(Auth::user());
    $canCancel = Auth::user()->isAdmin() && $order->canBeCancelled();
    $canDelete = Auth::user()->isAdmin();
    $paymentErrors = $errors->hasAny(['amount', 'method', 'paid_at', 'note']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-xl font-semibold text-slate-900">Pedido #{{ $order->getKey() }}</h1>
                <x-order-status :status="$order->status" />
            </div>

            <a href="{{ route('pedidos.index') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
                Volver a pedidos
            </a>
        </div>
    </x-slot>

    @if ($errors->any() && ! $paymentErrors)
        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
            <h2 class="font-semibold text-slate-900">Datos del pedido</h2>

            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cliente</dt>
                    <dd class="mt-1 text-sm font-medium">
                        @if ($order->customer)
                            <a href="{{ route('clientes.show', $order->customer) }}" class="text-emerald-700 hover:underline">
                                {{ $order->customer->name }}
                            </a>
                        @else
                            <span class="text-slate-700">Consumidor final</span>
                        @endif
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Registrado</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Registrado por</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $order->user->name }}</dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Pollos</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $order->items->count() }}</dd>
                </div>

                @if ($order->delivery_date)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Entrega prevista</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $order->delivery_date->format('d/m/Y') }}</dd>
                    </div>
                @endif

                @if ($order->delivered_at)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Entregado</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $order->delivered_at->format('d/m/Y H:i') }}</dd>
                    </div>
                @endif

                @if ($order->paid_at)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Cobrado</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $order->paid_at->format('d/m/Y H:i') }}</dd>
                    </div>
                @endif

                @if ($order->cancelled_at)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Anulado</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $order->cancelled_at->format('d/m/Y H:i') }}</dd>
                    </div>
                @endif

                @if ($order->price_per_lb !== null)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Precio por libra</dt>
                        <dd class="mt-1 text-sm text-slate-900"><x-money :value="$order->price_per_lb" /> / lb</dd>
                    </div>
                @endif

                @if ($order->note)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Nota</dt>
                        <dd class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ $order->note }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
            <div class="rounded-xl bg-slate-900 p-4 text-white shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total</p>
                @if ($order->isReserved())
                    <p class="mt-1 text-sm text-slate-300">Se calcula al entregar</p>
                @else
                    <p class="mt-1 text-2xl font-semibold"><x-money :value="$order->total" /></p>
                @endif
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Pagado</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">
                    @if ($order->isReserved())
                        <span class="text-slate-400">—</span>
                    @else
                        <x-money :value="$order->paidAmount()" />
                    @endif
                </p>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Saldo</p>
                <p class="mt-1 text-2xl font-semibold {{ $order->balance() > 0 ? 'text-amber-600' : 'text-emerald-600' }}">
                    @if ($order->isReserved())
                        <span class="text-slate-400">—</span>
                    @else
                        <x-money :value="$order->balance()" />
                    @endif
                </p>

                @if ($order->isDelivered() && $order->balance() > 0)
                    <div class="mt-3 space-y-2">
                        <button
                            type="button"
                            x-data
                            x-on:click="document.getElementById('amount').value = '{{ number_format($order->balance(), 2, '.', '') }}'; $dispatch('open-modal', 'registrar-pago')"
                            class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Cobrar saldo
                        </button>

                        <button
                            type="button"
                            x-data
                            x-on:click="document.getElementById('amount').value = ''; $dispatch('open-modal', 'registrar-pago')"
                            class="w-full rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                        >
                            Abonar
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Pollos</h2>
        </div>

        {{-- Escritorio --}}
        <table class="hidden min-w-full divide-y divide-slate-200 text-sm md:table">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">Pollo</th>
                    <th class="px-5 py-3 text-right">Peso</th>
                    <th class="px-5 py-3 text-right">Precio</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($order->items as $item)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">Pollo {{ $item->position }}</td>
                        <td class="px-5 py-3 text-right text-slate-700">
                            @if ($item->weight_lb !== null)
                                <x-weight :value="$item->weight_lb" />
                            @else
                                <span class="text-slate-400">Pendiente</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-medium text-slate-900">
                            @if ($item->price !== null)
                                <x-money :value="$item->price" />
                            @else
                                <span class="text-xs text-slate-400">Se calcula al entregar</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Celular --}}
        <ul class="divide-y divide-slate-100 md:hidden">
            @foreach ($order->items as $item)
                <li class="px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-medium text-slate-900">Pollo {{ $item->position }}</p>
                            <p class="text-xs text-slate-400">
                                @if ($item->weight_lb !== null)
                                    <x-weight :value="$item->weight_lb" />
                                @else
                                    Peso pendiente
                                @endif
                            </p>
                        </div>
                        <p class="font-semibold text-slate-900">
                            @if ($item->price !== null)
                                <x-money :value="$item->price" />
                            @else
                                <span class="text-xs font-normal text-slate-400">Se calcula al entregar</span>
                            @endif
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-4">
            <p class="text-sm font-semibold text-slate-700">Total</p>
            <p class="text-lg font-semibold text-slate-900">
                @if ($order->isReserved())
                    <span class="text-sm font-normal text-slate-400">Se calcula al entregar</span>
                @else
                    <x-money :value="$order->total" />
                @endif
            </p>
        </div>
    </div>

    @if ($order->isReserved())
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                La reserva todavía no tiene total. Al entregar se registran los pesos y se calcula el precio.
            </p>

            <div class="flex flex-wrap gap-2">
                @if ($canEdit)
                    <a href="{{ route('pedidos.edit', $order) }}" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                        Editar
                    </a>
                @endif

                <a href="{{ route('pedidos.deliver.edit', $order) }}" class="inline-flex justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Entregar
                </a>
            </div>
        </div>
    @elseif ($canEdit)
        <div class="mt-4 flex justify-end">
            <a href="{{ route('pedidos.edit', $order) }}" class="inline-flex justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Editar pedido
            </a>
        </div>
    @endif

    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-slate-900">Pagos</h2>
            <span class="text-xs text-slate-400">
                {{ $order->payments->count() }} {{ $order->payments->count() === 1 ? 'pago' : 'pagos' }}
            </span>
        </div>

        @if ($order->payments->isEmpty())
            <p class="px-5 py-6 text-sm text-slate-500">Todavía no se registraron pagos para este pedido.</p>
        @else
            {{-- Escritorio --}}
            <table class="hidden min-w-full divide-y divide-slate-200 text-sm md:table">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Medio</th>
                        <th class="px-5 py-3">Registrado por</th>
                        <th class="px-5 py-3">Nota</th>
                        <th class="px-5 py-3 text-right">Monto</th>
                        @if ($canDelete)
                            <th class="px-5 py-3 text-right">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($order->payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $payment->paid_at->format('d/m/Y') }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $payment->method->label() }}</td>
                            <td class="px-5 py-3 text-slate-700">{{ $payment->user->name }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $payment->note ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-semibold text-emerald-700"><x-money :value="$payment->amount" /></td>
                            @if ($canDelete)
                                <td class="px-5 py-3 text-right">
                                    <form method="POST" action="{{ route('pedidos.payments.destroy', [$order, $payment]) }}" onsubmit="return confirm('¿Eliminar este cobro de {{ number_format((float) $payment->amount, 2, ',', '.') }}? El saldo del pedido se recalculará.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-rose-700 hover:underline">Eliminar</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Celular --}}
            <ul class="divide-y divide-slate-100 md:hidden">
                @foreach ($order->payments as $payment)
                    <li class="px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-slate-900"><x-money :value="$payment->amount" /></p>
                                <p class="text-xs text-slate-500">{{ $payment->method->label() }} · {{ $payment->paid_at->format('d/m/Y') }}</p>
                                <p class="text-xs text-slate-400">{{ $payment->user->name }}</p>
                                @if ($payment->note)
                                    <p class="mt-1 text-xs text-slate-500">{{ $payment->note }}</p>
                                @endif
                            </div>
                            @if ($canDelete)
                                <form method="POST" action="{{ route('pedidos.payments.destroy', [$order, $payment]) }}" onsubmit="return confirm('¿Eliminar este cobro? El saldo del pedido se recalculará.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-rose-700">Eliminar</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($canCancel)
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-rose-900">Anular pedido</h2>
                    <p class="mt-1 text-sm text-rose-700">
                        El pedido quedará anulado. Esta acción no se puede deshacer.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('pedidos.cancel', $order) }}"
                    onsubmit="return confirm('¿Anular el pedido #{{ $order->getKey() }}? Esta acción no se puede deshacer.')"
                >
                    @csrf

                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 sm:w-auto">
                        Anular pedido
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($canDelete)
        <div class="mt-6 rounded-xl border border-rose-200 bg-rose-50 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-rose-900">Eliminar pedido</h2>
                    <p class="mt-1 text-sm text-rose-700">
                        Elimina permanentemente el pedido, sus pollos y todos sus cobros.
                    </p>
                </div>

                <form method="POST" action="{{ route('pedidos.destroy', $order) }}" onsubmit="return confirm('¿Eliminar permanentemente el pedido #{{ $order->getKey() }}? Esta acción borrará también sus cobros y no se puede deshacer.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-800 sm:w-auto">
                        Eliminar pedido
                    </button>
                </form>
            </div>
        </div>
    @endif

    @if ($order->isDelivered() && $order->balance() > 0)
        <x-modal name="registrar-pago" :show="$paymentErrors" focusable>
            <form method="POST" action="{{ route('pedidos.payments.store', $order) }}" class="space-y-5 p-6">
                @csrf

                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Registrar pago</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Saldo pendiente: <strong class="text-slate-900"><x-money :value="$order->balance()" /></strong>.
                        Si el pago cubre el saldo, el pedido queda cobrado.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="amount" value="Monto" />
                        <div class="relative mt-1">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">$</span>
                            <x-text-input
                                id="amount"
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                max="{{ number_format($order->balance(), 2, '.', '') }}"
                                class="block w-full pl-7"
                                :value="old('amount')"
                                required
                                autofocus
                            />
                        </div>
                        <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                    </div>

                    <div>
                        <x-input-label for="method" value="Medio de pago" />
                        <select id="method" name="method" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                            @foreach (\App\Enums\PaymentMethod::cases() as $method)
                                <option value="{{ $method->value }}" @selected(old('method', \App\Enums\PaymentMethod::Efectivo->value) === $method->value)>
                                    {{ $method->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('method')" />
                    </div>

                    <div>
                        <x-input-label for="paid_at" value="Fecha de pago" />
                        <x-text-input id="paid_at" name="paid_at" type="date" class="mt-1 block w-full" :value="old('paid_at', now()->format('Y-m-d'))" />
                        <x-input-error class="mt-2" :messages="$errors->get('paid_at')" />
                    </div>

                    <div>
                        <x-input-label for="payment_note" value="Nota (opcional)" />
                        <x-text-input id="payment_note" name="note" type="text" class="mt-1 block w-full" :value="old('note')" maxlength="255" />
                        <x-input-error class="mt-2" :messages="$errors->get('note')" />
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" x-on:click="$dispatch('close')" class="rounded-lg px-4 py-2 text-sm font-medium text-slate-500 hover:text-slate-700">
                        Cancelar
                    </button>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Registrar pago
                    </button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
