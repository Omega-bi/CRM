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

<div class="flex items-start gap-8 max-md:flex-col">
    <nav class="w-full shrink-0 space-y-1 pb-4 text-sm md:w-[220px]" aria-label="{{ __('Settings') }}">
        @foreach ($settingsLinks as $link)
            <a
                href="{{ route($link['route']) }}"
                wire:navigate
                class="block truncate border-l-2 py-1.5 pl-3 transition {{ $link['active'] ? 'border-[var(--color-brand-primary)] text-[var(--color-brand-primary)]' : 'border-transparent text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}"
            >
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch">
        @if ($heading)
            <flux:heading>{{ $heading }}</flux:heading>
        @endif

        @if ($subheading)
            <flux:subheading>{{ $subheading }}</flux:subheading>
        @endif

        <div class="w-full {{ $fullWidth ? 'max-w-none' : 'max-w-lg' }}">
            {{ $slot }}
        </div>
    </div>
</div>
