
<section class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 p-4">
  <div class="flex items-start justify-between gap-4 py-4">
    <div>
      <flux:heading size="lg">{{ __('Company departments') }}</flux:heading>
    </div>

    <flux:button variant="ghost" size="xs" icon="question-mark-circle" class="text-[#ff8a00]" />
  </div>

  <div class="pb-3 mt-6">
    <div class="flex items-center justify-between gap-4 text-sm">
      <flux:modal.trigger name="create-company-structure">
        <button type="button" class="inline-flex items-center gap-2 text-[#013763] hover:underline">
          <flux:icon name="plus-circle" class="size-4" />
          <span>{{ __('Add department') }}</span>
        </button>
      </flux:modal.trigger>

      <button type="button" class="inline-flex items-center gap-2 text-[#013763] hover:underline">
        <flux:icon name="arrow-down-tray" class="size-4" />
        <span>{{ __('Download list') }}</span>
      </button>
    </div>

    <div class="mt-6 space-y-4">
      @forelse ($this->departments->whereNull('parent_id') as $department)
        @include('components.settings.workers.partials.department-tree-item', [
          'department' => $department,
          'level' => 0,
        ])
      @empty
        <div class="rounded-md bg-zinc-100 px-4 py-8 text-center text-sm text-zinc-500 dark:bg-zinc-800/70 dark:text-zinc-400">
          {{ __('No departments yet') }}
        </div>
      @endforelse
    </div>
  </div>
</section>
