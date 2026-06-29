<?php

use Livewire\Component;

new class extends Component {}; ?>

<section class="mt-10 space-y-6">
    <details class="group">
        <summary class="list-none cursor-pointer">
            <div class="inline-flex items-center gap-2 text-sm font-medium text-[#b91c1c] hover:underline">
                <span>{{ __('Other account actions') }}</span>
                <flux:icon.chevron-down class="size-4 transition-transform group-open:rotate-180" />
            </div>
        </summary>

        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
            <flux:subheading>{{ __('Delete your account and all of its resources') }}</flux:subheading>

            <flux:modal.trigger name="confirm-user-deletion">
                <flux:button variant="danger" size="sm" class="mt-3" data-test="delete-user-button">
                    {{ __('Delete account') }}
                </flux:button>
            </flux:modal.trigger>
        </div>
    </details>

    <livewire:pages::settings.delete-user-modal />
</section>
