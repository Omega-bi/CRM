@props([
    'heading' => null,
    'subheading' => null,
    'fullWidth' => false,
])

@php
    $settingsLinks = [
        ['label' => __('Profile'), 'route' => 'profile.edit', 'active' => request()->routeIs('profile.edit')],
        ['label' => __('Workers'), 'route' => 'workers.index', 'active' => request()->routeIs('workers.index')],
        ['label' => __('Roles & permissions'), 'route' => 'roles.index', 'active' => request()->routeIs('roles.index')],
        ['label' => __('Security'), 'route' => 'security.edit', 'active' => request()->routeIs('security.edit')],
        ['label' => __('Workspaces'), 'route' => 'workspaces.index', 'active' => request()->routeIs('workspaces.*')],
        ['label' => __('Appearance'), 'route' => 'appearance.edit', 'active' => request()->routeIs('appearance.edit')],
    ];
@endphp

<div class="flex h-full min-h-0 items-start gap-8 max-md:flex-col md:items-stretch">
    <nav class="w-full shrink-0 space-y-1.5 overflow-y-auto pb-4 text-sm md:sticky md:top-0 md:h-full md:max-h-full md:w-[220px]" aria-label="{{ __('Settings') }}">
        @foreach ($settingsLinks as $link)
            <a
                href="{{ route($link['route']) }}"
                wire:navigate
                class="block truncate rounded-md px-3 py-2 transition {{ $link['active'] ? 'bg-white font-medium text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100' : 'text-zinc-600 hover:bg-white hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-950 dark:hover:text-zinc-100' }}"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    <flux:separator class="md:hidden" />

    <div class="flex min-h-0 flex-1 flex-col">
        @if ($heading)
            <flux:heading>{{ $heading }}</flux:heading>
        @endif

        @if ($subheading)
            <flux:subheading>{{ $subheading }}</flux:subheading>
        @endif

        <div class="flex-1 w-full min-h-0 overflow-hidden {{ $fullWidth ? 'max-w-none' : 'max-w-lg' }}">
            <div class="h-full min-h-0">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
