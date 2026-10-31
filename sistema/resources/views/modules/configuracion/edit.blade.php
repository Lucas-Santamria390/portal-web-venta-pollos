<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Configuración</h1>
                <p class="text-sm text-slate-500">Precio por libra y forma de calcular el precio de los pollos</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('configuracion.update') }}" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Precio del pollo</h2>
                <p class="mt-1 text-sm text-slate-500">
                    El precio por libra se usa como referencia al crear reservas y se propone al entregar.
                </p>

                <div class="mt-4 sm:max-w-xs">
                    <x-input-label for="price_per_lb" value="Precio por libra" />
                    <div class="relative mt-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">$</span>
                        <x-text-input
                            id="price_per_lb"
                            name="price_per_lb"
                            type="number"
                            step="0.01"
                            min="0"
                            max="999999.99"
                            class="block w-full pl-7"
                            :value="old('price_per_lb', number_format((float) $setting->price_per_lb, 2, '.', ''))"
                            required
                        />
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('price_per_lb')" />
                </div>

                <div class="mt-6 rounded-lg border border-slate-200 p-4">
                    <input type="hidden" name="auto_price" value="0">

                    <label for="auto_price" class="flex items-start gap-3">
                        <input
                            id="auto_price"
                            name="auto_price"
                            type="checkbox"
                            value="1"
                            @checked(old('auto_price', $setting->auto_price))
                            class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500"
                        >

                        <span>
                            <span class="block text-sm font-medium text-slate-900">Calcular el precio automáticamente</span>
                            <span class="mt-1 block text-sm text-slate-500">
                                Si está activo, el precio de cada pollo es su peso en libras multiplicado por el precio por libra.
                                Si lo desactivas, podrás escribir el precio de cada pollo al vender o entregar, sin dejar de registrar su peso.
                            </span>
                        </span>
                    </label>

                    <x-input-error class="mt-2" :messages="$errors->get('auto_price')" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    Guardar configuración
                </button>

                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-slate-500 hover:text-slate-700">
                    Volver al inicio
                </a>
            </div>
        </form>
    </div>
</x-app-layout>
