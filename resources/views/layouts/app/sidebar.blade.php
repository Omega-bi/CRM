<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  @include('partials.head')
</head>
@php
  $pageHeading = request()->is('settings*')
    ? __('Settings')
    : ($title ?? __('Dashboard'));
@endphp

<body class="min-h-screen bg-zinc-50 dark:bg-black">
  <flux:sidebar sticky collapsible="mobile"
    class="border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-black">
    <flux:sidebar.header>
      <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
      <flux:sidebar.collapse class="lg:hidden" />
    </flux:sidebar.header>

    <livewire:workspace::switcher />

    <flux:sidebar.nav>
      <flux:sidebar.group :heading="__('Platform')" class="grid">
        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
          wire:navigate>
          {{ __('Dashboard') }}
        </flux:sidebar.item>
      </flux:sidebar.group>
    </flux:sidebar.nav>

    <flux:spacer />

    <flux:sidebar.nav>
      <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
        {{ __('Repository') }}
      </flux:sidebar.item>

      <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
        {{ __('Documentation') }}
      </flux:sidebar.item>
    </flux:sidebar.nav>

    <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
  </flux:sidebar>

  <flux:header class="h-12 gap-3 border-b border-zinc-200 bg-white px-6 lg:px-8 dark:border-zinc-800 dark:bg-black">
    <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

    <h1 class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">
      {{ $pageHeading }}
    </h1>

    <flux:spacer />

    <livewire:language-switcher />

    <button type="button" x-data x-on:click="$flux.appearance = $flux.dark ? 'light' : 'dark'"
      class="inline-flex h-12 w-12 cursor-pointer items-center justify-center text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-950 dark:hover:text-white"
      :aria-label="$flux.dark ? @js(__('Light')) : @js(__('Dark'))">
      <flux:icon.sun x-show="$flux.dark" x-cloak class="size-5" />
      <flux:icon.moon x-show="! $flux.dark" x-cloak class="size-5" />
    </button>

    <flux:dropdown position="top" align="end" class="lg:hidden">
      <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

      <flux:menu>
        <flux:menu.radio.group>
          <div class="p-0 text-sm font-normal">
            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
              <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

              <div class="grid flex-1 text-start text-sm leading-tight">
                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
              </div>
            </div>
          </div>
        </flux:menu.radio.group>

        <flux:menu.separator />

        <flux:menu.radio.group>
          <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
            {{ __('Settings') }}
          </flux:menu.item>
        </flux:menu.radio.group>

        <flux:menu.separator />

        <form method="POST" action="{{ route('logout') }}" class="w-full">
          @csrf
          <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer"
            data-test="logout-button">
            {{ __('Log out') }}
          </flux:menu.item>
        </form>
      </flux:menu>
    </flux:dropdown>
  </flux:header>

  {{ $slot }}

  <livewire:workspace::create-modal />

  @persist('toast')
  <flux:toast.group>
    <flux:toast />
  </flux:toast.group>
  @endpersist

  @fluxScripts
</body>

</html>
