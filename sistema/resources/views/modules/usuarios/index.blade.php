<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Usuarios</h1>
                <p class="text-sm text-slate-500">Cuentas del personal y permisos de acceso</p>
            </div>

            <a
                href="{{ route('usuarios.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
            >
                <span class="text-lg leading-none">+</span>
                Nuevo usuario
            </a>
        </div>
    </x-slot>

    <div class="space-y-4">
        <form method="GET" action="{{ route('usuarios.index') }}" class="flex gap-2 sm:max-w-md">
            <input
                type="search"
                name="buscar"
                value="{{ $search }}"
                placeholder="Buscar por nombre o correo"
                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
            >

            <button type="submit" class="rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                Buscar
            </button>
        </form>

        @if ($users->isEmpty())
            <x-empty-state
                title="No hay usuarios para mostrar"
                description="{{ $search !== '' ? 'No encontramos usuarios con ese criterio de búsqueda.' : 'Crea cuentas para que tu personal pueda entrar al portal con su propio correo y contraseña.' }}"
            >
                @if ($search === '')
                    <x-slot name="action">
                        <a href="{{ route('usuarios.create') }}" class="inline-flex rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Crear el primer usuario
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
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($users as $user)
                            @php($isSelf = auth()->id() === $user->getKey())

                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">
                                        {{ $user->name }}
                                        @if ($isSelf)
                                            <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Tu cuenta</span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-slate-500">{{ $user->email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->role === \App\Enums\UserRole::Admin ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $user->role->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $user->active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('usuarios.edit', $user) }}" class="text-sm font-medium text-emerald-700 hover:underline">
                                            Editar
                                        </a>

                                        @unless ($isSelf)
                                            <form method="POST" action="{{ route('usuarios.estado', $user) }}">
                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="text-sm font-medium {{ $user->active ? 'text-rose-600 hover:underline' : 'text-emerald-700 hover:underline' }}"
                                                >
                                                    {{ $user->active ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Celular --}}
            <div class="space-y-3 md:hidden">
                @foreach ($users as $user)
                    @php($isSelf = auth()->id() === $user->getKey())

                    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-slate-900">
                                    {{ $user->name }}
                                    @if ($isSelf)
                                        <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">Tu cuenta</span>
                                    @endif
                                </p>
                                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $user->active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-between gap-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->role === \App\Enums\UserRole::Admin ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                {{ $user->role->label() }}
                            </span>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('usuarios.edit', $user) }}" class="text-sm font-medium text-emerald-700 hover:underline">
                                    Editar
                                </a>

                                @unless ($isSelf)
                                    <form method="POST" action="{{ route('usuarios.estado', $user) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="text-sm font-medium {{ $user->active ? 'text-rose-600 hover:underline' : 'text-emerald-700 hover:underline' }}"
                                        >
                                            {{ $user->active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endunless
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div>
                {{ $users->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
