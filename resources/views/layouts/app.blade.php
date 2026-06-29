<x-layouts::app.sidebar :title="$title ?? null">
  <flux:main class="[grid-area:main] min-h-0 {{ request()->is('settings*') ? 'overflow-hidden' : 'overflow-y-auto' }} bg-zinc-50 p-6 [[data-flux-container]_&]:px-0 dark:bg-black">
    {{ $slot }}
  </flux:main>
</x-layouts::app.sidebar>
