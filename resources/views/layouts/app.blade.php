<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="[grid-area:main] bg-zinc-100 p-6 [[data-flux-container]_&]:px-0 dark:bg-black">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
