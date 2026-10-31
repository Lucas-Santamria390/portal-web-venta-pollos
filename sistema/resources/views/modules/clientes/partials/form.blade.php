@php($customer = $customer ?? null)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf

    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $customer?->name)" required autofocus maxlength="150" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="phone" value="Teléfono (opcional)" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $customer?->phone)" maxlength="30" />
            <p class="mt-1 text-xs text-slate-400">Se usa para llamar o abrir WhatsApp desde el detalle.</p>
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="note" value="Nota (opcional)" />
            <textarea
                id="note"
                name="note"
                rows="3"
                maxlength="1000"
                class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >{{ old('note', $customer?->note) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('note')" />
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5">
        <button type="submit" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            {{ $submitLabel }}
        </button>

        <a href="{{ $cancelUrl }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
            Cancelar
        </a>
    </div>
</form>
