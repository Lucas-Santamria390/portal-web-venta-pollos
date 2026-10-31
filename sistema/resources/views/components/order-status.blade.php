@props(['status'])

@php
    $classes = match ($status) {
        \App\Enums\OrderStatus::Reservado => 'bg-amber-100 text-amber-700',
        \App\Enums\OrderStatus::Entregado => 'bg-sky-100 text-sky-700',
        \App\Enums\OrderStatus::Cobrado => 'bg-emerald-100 text-emerald-700',
        \App\Enums\OrderStatus::Anulado => 'bg-rose-100 text-rose-700',
    };
@endphp

<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
    {{ $status->label() }}
</span>
