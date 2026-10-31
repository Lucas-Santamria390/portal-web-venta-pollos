<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Perfil</h1>
            <p class="text-sm text-slate-500">Datos de tu cuenta</p>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>
