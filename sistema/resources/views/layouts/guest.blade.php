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
    <body class="font-sans text-slate-900 antialiased">
        <div class="flex min-h-screen items-center justify-center bg-slate-100 px-4 py-8">
            <div class="w-full max-w-md">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-600 font-bold text-white">
                        PV
                    </div>
                    <div>
                        <p class="text-lg font-semibold leading-tight">Portal de Ventas</p>
                        <p class="text-xs text-slate-500">Pollos crudos</p>
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    {{ $slot }}
                </div>

                <p class="mt-4 text-center text-xs text-slate-400">
                    Sistema interno · Solo personal autorizado
                </p>
            </div>
        </div>
    </body>
</html>
