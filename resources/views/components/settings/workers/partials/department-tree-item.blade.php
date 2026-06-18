@php
  $departmentEmployees = $department->employees;
  $visibleEmployees = $departmentEmployees->take(10);
  $extraEmployeesCount = $departmentEmployees->count() - $visibleEmployees->count();
  $indentOffset = $level * 24;
  $avatarBackgrounds = ['bg-sky-600', 'bg-emerald-600', 'bg-amber-500', 'bg-rose-600', 'bg-violet-600', 'bg-cyan-600'];
@endphp

<div class="space-y-3 transition-transform duration-150" data-department-tree-name="{{ $department->name }}"
  style="margin-left: {{ $indentOffset }}px;"
  x-bind:class="draggedDepartmentId === {{ $department->id }} ? 'scale-[0.99] opacity-45' : ''">
  <div class="flex items-start gap-3">
    @if ($level > 0)
      <div class="flex w-5 shrink-0 items-start justify-center pt-3">
        <flux:icon name="arrow-turn-down-right" class="size-5 shrink-0 text-zinc-500 stroke-2" />
      </div>
    @endif

    <div
      class="flex min-w-0 flex-1 cursor-grab items-center justify-between gap-3 rounded-md bg-zinc-50 px-5 py-2 transition-all duration-150 active:cursor-grabbing dark:bg-zinc-800/70"
      draggable="true" x-on:dragstart="
        draggedDepartmentId = {{ $department->id }};
        draggedDepartmentName = @js($department->name);
        dragX = $event.clientX;
        dragY = $event.clientY;
        $event.dataTransfer.effectAllowed = 'move';
        $event.dataTransfer.setData('text/plain', '{{ $department->id }}');
        $event.dataTransfer.setDragImage($event.currentTarget, 24, 20);
      " x-on:drag="dragX = $event.clientX || dragX; dragY = $event.clientY || dragY"
      x-on:dragend="draggedDepartmentId = null; draggedDepartmentName = ''; dropDepartmentId = null"
      x-on:dragover.prevent="if (draggedDepartmentId && draggedDepartmentId !== {{ $department->id }}) dropDepartmentId = {{ $department->id }}"
      x-on:dragleave="if (dropDepartmentId === {{ $department->id }}) dropDepartmentId = null" x-on:drop.prevent="
        if (draggedDepartmentId && draggedDepartmentId !== {{ $department->id }}) {
          $wire.moveDepartment(draggedDepartmentId, {{ $department->id }});
        }
        draggedDepartmentId = null;
        draggedDepartmentName = '';
        dropDepartmentId = null;
      "
      x-bind:class="dropDepartmentId === {{ $department->id }} ? 'translate-x-1 bg-[#eaf3ff] ring-1 ring-[#1f8fff] shadow-sm dark:bg-[#0f2a44]' : ''">
      <div class="flex min-w-0 items-center gap-3">
        <span title="{{ __('Drag department') }}"
          class="inline-flex size-6 shrink-0 items-center justify-center text-zinc-400 dark:text-zinc-500">
          <flux:icon name="bars-3" class="size-4" />
        </span>

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

      <button type="button" wire:click="editDepartment({{ $department->id }})" draggable="false" x-on:dragover.stop
        x-on:drop.stop x-on:dragstart.stop.prevent
        class="inline-flex size-8 shrink-0 items-center justify-center text-zinc-600  hover:text-zinc-900 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-100">
        <flux:icon name="cog-6-tooth" class="size-4" />
      </button>
    </div>
  </div>

  <div
    class="ml-10 hidden rounded-md border border-dashed border-[#1f8fff] bg-[#f4f9ff] px-4 py-2 text-xs font-medium text-[#1f8fff] dark:bg-[#0f2a44]/60"
    x-bind:class="dropDepartmentId === {{ $department->id }} ? 'block' : 'hidden'">
    {{ __('Move here') }}
  </div>

  @foreach ($department->children as $childDepartment)
    @include('components.settings.workers.partials.department-tree-item', [
      'department' => $childDepartment,
      'level' => $level + 1,
    ])
  @endforeach
</div>
