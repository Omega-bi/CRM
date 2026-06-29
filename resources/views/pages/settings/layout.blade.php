@props([
    'heading' => null,
    'subheading' => null,
    'fullWidth' => false,
    'contentScroll' => true,
])

@php
    $currentPath = request()->path();
    $settingsLinks = [
        ['label' => __('Profile'), 'route' => 'profile.edit', 'active' => request()->routeIs('profile.edit') || $currentPath === 'settings/profile'],
        ['label' => __('Workers'), 'route' => 'workers.index', 'active' => request()->routeIs('workers.index') || $currentPath === 'settings/workers'],
        ['label' => __('Roles & permissions'), 'route' => 'roles.index', 'active' => request()->routeIs('roles.index') || $currentPath === 'settings/roles'],
        ['label' => __('Security'), 'route' => 'security.edit', 'active' => request()->routeIs('security.edit') || $currentPath === 'settings/security'],
        ['label' => __('Workspaces'), 'route' => 'workspaces.index', 'active' => request()->routeIs('workspaces.*') || request()->is('workspaces*')],
        ['label' => __('Appearance'), 'route' => 'appearance.edit', 'active' => request()->routeIs('appearance.edit') || $currentPath === 'settings/appearance'],
    ];
    $activeSettingsLabel = collect($settingsLinks)
      ->firstWhere('active', true)['label'] ?? __('Settings');
@endphp

<div class="flex h-[calc(100vh-6rem)] min-h-0 flex-col gap-2 overflow-hidden">
    <nav aria-label="{{ __('Breadcrumb') }}" class="sticky top-0 z-20 shrink-0 -mx-1 px-1 pb-2 text-xs text-zinc-500 dark:text-zinc-400">
        <a href="{{ route('dashboard') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">
            {{ __('Dashboard') }}
        </a>
        <span class="mx-2 text-zinc-300 dark:text-zinc-600">/</span>
        <a href="{{ route('profile.edit') }}" class="hover:text-zinc-900 dark:hover:text-zinc-100">
            {{ __('Settings') }}
        </a>
        <span class="mx-2 text-zinc-300 dark:text-zinc-600">/</span>
        <span class="text-zinc-900 dark:text-zinc-100">{{ $activeSettingsLabel }}</span>
    </nav>

    <div class="flex min-h-0 flex-1 items-start gap-8 overflow-hidden max-md:flex-col md:items-stretch">
        <nav class="sticky top-0 w-full shrink-0 space-y-1.5 overflow-y-auto pb-4 text-sm md:h-full md:max-h-full md:w-[220px]" aria-label="{{ __('Settings') }}">
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

        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
            <div class="flex-1 w-full min-h-0 {{ $contentScroll ? 'overflow-y-auto' : 'overflow-hidden' }} {{ $fullWidth ? 'max-w-none' : 'max-w-lg' }}">
                <div class="{{ $contentScroll ? 'min-h-full' : 'h-full min-h-0' }}">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</div>
