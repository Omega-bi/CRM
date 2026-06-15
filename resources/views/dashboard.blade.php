<x-layouts::app :title="__('Dashboard')">
    <livewire:pages::workspaces.pending-invitations-modal />

    @if (! auth()->user()->workspaces()->exists())
        <div class="fixed inset-y-0 right-0 left-0 flex items-center justify-center px-6 lg:left-64">
            <div class="flex max-w-xl flex-col items-center text-center">
                <div class="mb-6 flex size-[100px] items-center justify-center rounded-full border border-zinc-200 bg-white text-[#013763]">
                    <flux:icon.building-office-2 class="size-14" />
                </div>

                <flux:heading size="xl">{{ __('Create your workspace') }}</flux:heading>
                <flux:text class="mt-2 max-w-md text-zinc-500">
                    {{ __('Set up the company workspace before adding projects, employees, and construction records.') }}
                </flux:text>

                <div class="mt-6">
                    <flux:modal.trigger name="create-workspace-switcher">
                        <flux:button variant="primary" icon="plus" data-test="dashboard-create-workspace-button">
                            {{ __('Create workspace') }}
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </div>
    @else
        <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                    <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
            <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <x-placeholder-pattern class="absolute inset-0 size-full stroke-gray-900/20 dark:stroke-neutral-100/20" />
            </div>
        </div>
    @endif
</x-layouts::app>
