<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-neutral-100 antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div
            class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-cover bg-center bg-no-repeat p-6 md:p-10"
            style="background-image: linear-gradient(rgba(244, 245, 246, 0.72), rgba(244, 245, 246, 0.72)), url('{{ asset('images/auth-construction-bg.png') }}');"
        >
            <div class="absolute end-4 top-4 z-20">
                <livewire:language-switcher />
            </div>
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    @if ($showLogo ?? true)
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                    </span>
                    @endif
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
