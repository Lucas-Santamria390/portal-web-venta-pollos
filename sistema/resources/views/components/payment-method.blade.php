@props(['method'])

@php
    $paymentMethod = $method instanceof \App\Enums\PaymentMethod
        ? $method
        : \App\Enums\PaymentMethod::tryFrom((string) $method);

    $classes = match ($paymentMethod) {
        \App\Enums\PaymentMethod::Efectivo => 'bg-emerald-100 text-emerald-700',
        \App\Enums\PaymentMethod::Transferencia => 'bg-sky-100 text-sky-700',
        \App\Enums\PaymentMethod::Tarjeta => 'bg-violet-100 text-violet-700',
        \App\Enums\PaymentMethod::Otro => 'bg-slate-100 text-slate-600',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

@if ($paymentMethod)
    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $classes }}">
        {{ $paymentMethod->label() }}
    </span>
@endif
