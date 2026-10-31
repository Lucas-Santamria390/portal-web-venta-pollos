<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Portal de Ventas') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        @php
            $links = [
                ['route' => 'dashboard', 'label' => 'Inicio', 'icon' => 'home'],
                ['route' => 'pedidos.index', 'label' => 'Pedidos', 'icon' => 'receipt', 'match' => 'pedidos.*'],
                ['route' => 'por-cobrar.index', 'label' => 'Por cobrar', 'icon' => 'cash'],
                ['route' => 'clientes.index', 'label' => 'Clientes', 'icon' => 'users'],
            ];

            if (Auth::user()->isAdmin()) {
                $links[] = ['route' => 'usuarios.index', 'label' => 'Usuarios', 'icon' => 'shield', 'match' => 'usuarios.*'];
                $links[] = ['route' => 'configuracion.edit', 'label' => 'Configuración', 'short' => 'Ajustes', 'icon' => 'settings', 'match' => 'configuracion.*'];
            }
        @endphp

        <div class="min-h-screen lg:flex">
            <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0 bg-slate-900 text-slate-100">
                <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500 font-bold text-slate-900">
                        PV
                    </div>
                    <div>
                        <p class="font-semibold leading-tight">Portal de Ventas</p>
                        <p class="text-xs text-slate-400">Pollos crudos</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-1 px-3 py-4">
                    @foreach ($links as $link)
                        <a
                            href="{{ route($link['route']) }}"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($link['match'] ?? $link['route']) ? 'bg-emerald-500 text-slate-900' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}"
                        >
                            @include('layouts.partials.icon', ['name' => $link['icon']])
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-white/10 p-4">
                    <p class="truncate text-sm font-medium">{{ Auth::user()->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ Auth::user()->email }}</p>

                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="text-xs font-medium text-slate-300 transition hover:text-white">
                            Cerrar sesión
                        </button>
                    </form>
                </div>
            </aside>

            <div class="flex-1 lg:pl-64">
                <header class="sticky top-0 z-30 bg-slate-900 text-white lg:hidden">
                    <div class="flex h-14 items-center justify-between px-4">
                        <div class="flex items-center gap-2">
                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 text-sm font-bold text-slate-900">
                                PV
                            </div>
                            <span class="font-semibold">Portal de Ventas</span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs font-medium text-slate-300 hover:text-white">
                                Salir
                            </button>
                        </form>
                    </div>
                </header>

                @isset($header)
                    <div class="border-b border-slate-200 bg-white">
                        <div class="px-4 py-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                <main class="px-4 py-6 pb-24 sm:px-6 lg:px-8 lg:pb-10">
                    @if (session('status'))
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">
            @php($mobileColumns = match (count($links)) {
                5 => 'grid-cols-5',
                6 => 'grid-cols-6',
                default => 'grid-cols-4',
            })

            <div class="grid {{ $mobileColumns }}">
                @foreach ($links as $link)
                    <a
                        href="{{ route($link['route']) }}"
                        class="flex flex-col items-center justify-center gap-1 py-2 {{ count($links) >= 6 ? 'text-[10px]' : 'text-[11px]' }} font-medium {{ request()->routeIs($link['match'] ?? $link['route']) ? 'text-emerald-600' : 'text-slate-500' }}"
                    >
                        @include('layouts.partials.icon', ['name' => $link['icon']])
                        <span class="max-w-full truncate px-1">{{ $link['short'] ?? $link['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>
    </body>
</html>
