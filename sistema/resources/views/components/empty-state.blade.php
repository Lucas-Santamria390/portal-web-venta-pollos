@props(['title', 'description'])

<div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
    <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ $description }}</p>

    @if (isset($action))
        <div class="mt-5">
            {{ $action }}
        </div>
    @endif
</div>
