<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="p-6 [grid-area:main] [[data-flux-container]_&]:px-0 bg-zinc-100">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
