<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="p-4 [grid-area:main] [[data-flux-container]_&]:px-0 bg-zinc-50">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
