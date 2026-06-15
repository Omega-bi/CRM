@php
  $departmentEmployees = $department->employees;
  $visibleEmployees = $departmentEmployees->take(10);
  $extraEmployeesCount = $departmentEmployees->count() - $visibleEmployees->count();
  $indentClass = $level > 0 ? 'ml-6' : '';
  $childIndentClass = $level > 0 ? 'ml-6' : 'ml-6';
@endphp

<div class="space-y-3 {{ $indentClass }}">
  <div class="flex items-center justify-between gap-3 rounded-md bg-zinc-100 px-5 py-3 dark:bg-zinc-800/70">
    <div class="flex min-w-0 items-center gap-3">
      @if ($level > 0)
        <flux:icon name="arrow-turn-down-right" class="size-5 shrink-0 text-zinc-500 stroke-2" />
      @endif

      <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
        {{ $department->name }}
      </span>

      @if ($visibleEmployees->isNotEmpty())
        <div class="flex items-center gap-1">
          @foreach ($visibleEmployees as $employee)
            <div class="flex size-6 items-center justify-center rounded-full bg-[#013763] text-[9px] font-medium text-white"
              title="{{ $employee->full_name ?: $employee->email }}">
              {{ Str::of($employee->full_name ?: $employee->email)->substr(0, 1)->upper() }}
            </div>
          @endforeach

          @if ($extraEmployeesCount > 0)
            <div class="flex size-6 items-center justify-center rounded-full bg-zinc-300 text-[9px] font-medium text-zinc-700 dark:bg-zinc-600 dark:text-zinc-100"
              title="{{ __('More employees') }}">
              +{{ $extraEmployeesCount }}
            </div>
          @endif
        </div>
      @endif
    </div>

    <button type="button"
      wire:click="editDepartment({{ $department->id }})"
      class="inline-flex size-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-200 hover:text-zinc-900 dark:hover:bg-zinc-700 dark:hover:text-zinc-100">
      <flux:icon name="cog-6-tooth" class="size-4" />
    </button>
  </div>

  @foreach ($department->positions as $position)
    <div class="flex items-center gap-3 {{ $childIndentClass }}">
      <flux:icon name="arrow-turn-down-right" class="size-5 shrink-0 text-zinc-600 stroke-2" />

      <div class="flex-1 rounded-md bg-zinc-100 px-5 py-3 dark:bg-zinc-800/70">
        <span class="text-sm text-zinc-900 dark:text-zinc-100">
          {{ $position->name }}
        </span>
      </div>
    </div>
  @endforeach

  @foreach ($department->children as $childDepartment)
    @include('components.settings.workers.partials.department-tree-item', [
      'department' => $childDepartment,
      'level' => $level + 1,
    ])
  @endforeach
</div>
