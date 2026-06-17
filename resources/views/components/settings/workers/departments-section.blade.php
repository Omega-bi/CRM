
<section class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950"
  x-data="{ draggedDepartmentId: null, draggedDepartmentName: '', dropDepartmentId: null, dragX: 0, dragY: 0 }"
  x-on:dragover.window="dragX = $event.clientX; dragY = $event.clientY">
  <div
    class="pointer-events-none fixed z-[9999] flex max-w-72 items-center gap-2 rounded-md border border-[#1f8fff] bg-white px-3 py-2 text-sm font-semibold text-zinc-900 shadow-lg dark:border-[#4aa3ff] dark:bg-black dark:text-zinc-50"
    x-show="draggedDepartmentId"
    x-cloak
    x-bind:style="`left: ${dragX + 14}px; top: ${dragY + 14}px`">
    <flux:icon name="bars-3" class="size-4 shrink-0 text-[#1f8fff]" />
    <span class="truncate" x-text="draggedDepartmentName"></span>
  </div>
  <div class="shrink-0 flex items-start justify-between gap-4">
    <div>
      <flux:heading size="lg">{{ __('Company structure') }}</flux:heading>
    </div>

    <flux:button variant="ghost" size="xs" icon="question-mark-circle" class="text-[#ff8a00]" />
  </div>

  <div class="mt-6 pb-3">
    <div class="flex items-center justify-between gap-4 text-sm">
      <flux:modal.trigger name="create-company-structure">
        <button type="button" class="inline-flex items-center gap-2 text-[#013763] hover:underline dark:text-[#8dc5ff]">
          <flux:icon name="plus-circle" class="size-4" />
          <span>{{ __('Add department') }}</span>
        </button>
      </flux:modal.trigger>

      <button type="button" class="inline-flex items-center gap-2 text-[#013763] hover:underline dark:text-[#8dc5ff]">
        <flux:icon name="arrow-down-tray" class="size-4" />
        <span>{{ __('Download list') }}</span>
      </button>
    </div>

    @php
      $rootDepartments = $this->departments->whereNull('parent_id');
      $visibleRootDepartments = $this->show_all_departments ? $rootDepartments : $rootDepartments->take(3);
      $hasMoreDepartments = $rootDepartments->count() > $visibleRootDepartments->count();
    @endphp

    <div class="mt-6 space-y-4">
      @forelse ($visibleRootDepartments as $department)
        @include('components.settings.workers.partials.department-tree-item', [
          'department' => $department,
          'level' => 0,
        ])
      @empty
        <div class="rounded-md bg-zinc-100 px-4 py-8 text-center text-sm text-zinc-500 dark:bg-black dark:text-zinc-400">
          {{ __('No departments yet') }}
        </div>
      @endforelse
    </div>

    @if ($hasMoreDepartments)
      <div class="mt-4 flex justify-start">
        <button type="button" wire:click="showAllDepartments"
          class="inline-flex items-center gap-2 text-sm font-medium text-[#013763] hover:underline dark:text-[#8dc5ff]">
          <span>{{ __('Show more') }}</span>
        </button>
      </div>
    @endif
  </div>
</section>
