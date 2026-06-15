@php
  $departmentEmployees = $department->employees;
  $visibleEmployees = $departmentEmployees->take(10);
  $extraEmployeesCount = $departmentEmployees->count() - $visibleEmployees->count();
  $indentOffset = $level * 24;
  $avatarBackgrounds = ['bg-sky-600', 'bg-emerald-600', 'bg-amber-500', 'bg-rose-600', 'bg-violet-600', 'bg-cyan-600'];
@endphp

<div class="space-y-3" style="margin-left: {{ $indentOffset }}px;">
  <div class="flex items-start gap-3">
    @if ($level > 0)
      <div class="flex w-5 shrink-0 items-start justify-center pt-3">
        <flux:icon name="arrow-turn-down-right" class="size-5 shrink-0 text-zinc-500 stroke-2" />
      </div>
    @endif

    <div class="flex min-w-0 flex-1 items-center justify-between gap-3 rounded-md bg-zinc-100 px-5 py-2 dark:bg-zinc-800/70">
      <div class="flex min-w-0 items-center gap-3">

        <span class="truncate text-sm font-semibold text-zinc-950 dark:text-zinc-50">
          {{ $department->name }}
        </span>

        @if ($visibleEmployees->isNotEmpty())
          <div class="flex items-center -space-x-1.5">
            @foreach ($visibleEmployees as $employee)
              @php
                $avatarBackground = $avatarBackgrounds[$loop->index % count($avatarBackgrounds)];
                $avatarLabel = Str::of($employee->full_name ?: $employee->email ?: '?')->trim()->substr(0, 1)->upper();
              @endphp
              <div
                class="relative flex size-7 items-center justify-center rounded-full {{ $avatarBackground }} text-[10px] font-semibold text-white ring-2 ring-white shadow-md dark:ring-zinc-900"
                title="{{ $employee->full_name ?: $employee->email }}">
                {{ $avatarLabel }}
              </div>
            @endforeach

            @if ($extraEmployeesCount > 0)
              <div
                class="relative flex size-7 items-center justify-center rounded-full bg-zinc-400 text-[10px] font-semibold text-white ring-2 ring-white shadow-md dark:bg-zinc-600 dark:ring-zinc-900"
                title="{{ __('More employees') }}">
                +{{ $extraEmployeesCount }}
              </div>
            @endif
          </div>
        @endif
      </div>

    <button type="button"
      wire:click="editDepartment({{ $department->id }})"
      class="inline-flex size-8 shrink-0 items-center justify-center text-zinc-600  hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-100">
      <flux:icon name="cog-6-tooth" class="size-4" />
    </button>
  </div>
  </div>

  @foreach ($department->children as $childDepartment)
    @include('components.settings.workers.partials.department-tree-item', [
      'department' => $childDepartment,
      'level' => $level + 1,
    ])
  @endforeach
</div>
