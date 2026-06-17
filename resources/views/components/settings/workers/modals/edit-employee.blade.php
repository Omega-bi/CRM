@php
  $editingEmployeeDepartments = $this->editingEmployeeDepartments;
  $editingEmployeeDepartmentsTotal = $this->editingEmployeeDepartmentsTotal;
  $workspaceProjects = $this->workspaceProjects;
  $workspaceProjectsTotal = $this->workspaceProjectsTotal;
@endphp

<flux:modal name="edit-employee" focusable class="max-w-none"
  style="width: 760px; max-width: calc(100vw - 32px);">
  <form wire:submit="updateEmployee" class="-m-6 flex max-h-[680px] min-h-[560px] flex-col overflow-hidden rounded-lg bg-white text-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
    <div class="flex h-[60px] shrink-0 items-center justify-between border-b border-zinc-200 px-6 dark:border-zinc-800">
      <div class="min-w-0 truncate text-xl font-medium text-slate-500 dark:text-slate-300">
        {{ $this->editing_employee_email ?: $this->editing_employee_full_name }}
      </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
      <div class="flex items-start gap-4">
        <div class="relative flex size-16 shrink-0 items-center justify-center">
          <flux:avatar
            :name="$this->editing_employee_full_name ?: $this->editing_employee_email"
            circle
            size="lg"
            color="auto"
            :color:seed="$this->editing_employee_email ?: $this->editing_employee_full_name"
            tooltip
            class="size-14 text-base font-semibold shadow-sm ring-1 ring-slate-200 dark:ring-zinc-800"
          />

          <span class="absolute bottom-1 left-1 size-4 rounded-full border-2 border-white bg-lime-500 shadow-sm dark:border-zinc-950"></span>
        </div>

        <div class="min-w-0 flex-1 space-y-2.5 text-sm">
          @php
            $inlineInputClass = 'min-w-0 flex-1 appearance-none border-0 border-b border-slate-200 bg-transparent px-1 py-0.5 text-sm text-zinc-700 shadow-none outline-none transition focus:border-[#1f8fff] focus:outline-none focus:ring-0 dark:border-zinc-700 dark:text-zinc-200 dark:focus:border-[#1f8fff]';
          @endphp

          <label class="flex items-center gap-2">
            <flux:icon name="briefcase" class="size-4 shrink-0 text-slate-300 dark:text-slate-600" />
            <span class="shrink-0 text-zinc-700 dark:text-zinc-300">{{ __('Position') }}:</span>
            <span class="min-w-0 flex-1 truncate border-b border-slate-200 px-1 py-0.5 text-sm text-[#1f8fff] dark:border-zinc-700">
              {{ $this->editingEmployeePositionLabel }}
            </span>
          </label>

          <label class="flex items-center gap-2">
            <flux:icon name="at-symbol" class="size-4 shrink-0 text-slate-300 dark:text-slate-600" />
            <span class="shrink-0 text-zinc-700 dark:text-zinc-300">{{ __('Email') }}:</span>
            <input wire:model.live.blur="editing_employee_email" type="email"
              placeholder="{{ __('Enter email address') }}"
              class="{{ $inlineInputClass }} placeholder:text-slate-400" />
          </label>

          <label class="flex items-center gap-2">
            <flux:icon name="phone" class="size-4 shrink-0 text-slate-300 dark:text-slate-600" />
            <span class="shrink-0 text-zinc-700 dark:text-zinc-300">{{ __('Phone') }}:</span>
            <input wire:model.live.blur="editing_employee_phone" type="text"
              placeholder="{{ __('Enter phone number') }}"
              class="{{ $inlineInputClass }} placeholder:text-slate-400" />
          </label>

          <label class="flex items-center gap-2">
            <flux:icon name="user" class="size-4 shrink-0 text-slate-300 dark:text-slate-600" />
            <span class="shrink-0 text-zinc-700 dark:text-zinc-300">{{ __('Full name') }}:</span>
            <input wire:model.live.blur="editing_employee_full_name" type="text"
              placeholder="{{ __('Enter full name') }}"
              class="{{ $inlineInputClass }} placeholder:text-slate-400" />
          </label>

          @if ($this->editing_employee_user_id === null)
            <button type="button" wire:click="openCreateEmployeeAccountModal"
              class="flex items-center gap-2 text-sm text-[#1f8fff] transition hover:text-[#0f78d6]">
              <flux:icon name="key" class="size-4 text-slate-300 dark:text-slate-600" />
              <span>{{ __('Create account') }}</span>
            </button>
          @else
            <div class="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
              <flux:icon name="key" class="size-4 text-emerald-400 dark:text-emerald-500" />
              <span>{{ __('Account created') }}</span>
            </div>
          @endif

          <button type="button" wire:click="deleteEmployee"
            wire:confirm="{{ __('Are you sure you want to delete the employee ":name"? This action cannot be undone.', ['name' => $this->editing_employee_full_name ?: $this->editing_employee_email]) }}"
            wire:loading.attr="disabled" wire:target="deleteEmployee"
            class="flex items-center gap-2 text-sm text-red-500 transition hover:text-red-600 disabled:pointer-events-none disabled:opacity-60 dark:text-red-400 dark:hover:text-red-300">
            <flux:icon name="trash" class="size-4 text-red-300 dark:text-red-500" />
            <span>{{ __('Delete employee from company') }}</span>
          </button>
        </div>
      </div>

      <div class="my-5 border-t border-slate-300 dark:border-zinc-800"></div>

      <section>
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Departments membership') }}:</h3>

        <div class="mt-4 h-[168px] overflow-y-auto pr-1">
          <div class="grid grid-cols-[minmax(0,1fr)_220px] gap-x-4 gap-y-2 text-sm">
            <div class="sticky top-0 z-10 bg-white pb-1 pl-6 text-xs text-slate-300 dark:bg-zinc-950">
              {{ __('Department name') }}
            </div>
            <div class="sticky top-0 z-10 bg-white pb-1 text-xs text-slate-300 dark:bg-zinc-950">
              {{ __('Role in department') }}
            </div>

            @foreach ($editingEmployeeDepartments as $department)
              <label class="flex min-w-0 items-center gap-2 text-zinc-600 dark:text-zinc-300">
                <input type="checkbox" wire:model.live="editing_employee_department_ids" value="{{ $department->id }}"
                  class="size-3.5 rounded border-slate-300 text-[#1f8fff] focus:ring-[#1f8fff]" />
                <span class="truncate">{{ $department->name }}</span>
              </label>

              @if ((int) $this->editing_employee_department_id === (int) $department->id)
                <x-ui.select
                  wire:key="editing-employee-staff-position-{{ $this->editing_employee_department_id }}"
                  model="editing_employee_staff_position_id"
                  :value="$editing_employee_staff_position_id"
                  :options="$this->editingEmployeeStaffPositions->pluck('name', 'id')->prepend(__('Not set'), '')->all()"
                  size="xs"
                />
              @else
                <span class="text-sm text-slate-400">{{ __('Not set') }}</span>
              @endif
            @endforeach

            @if ($editingEmployeeDepartmentsTotal > $this->editing_employee_departments_per_page)
              <div class="col-span-2 py-2">
                <x-settings.workers.partials.pagination
                  :current-page="$this->editing_employee_departments_page"
                  :total="$editingEmployeeDepartmentsTotal"
                  :per-page="$this->editing_employee_departments_per_page"
                  set-page-action="setEditingEmployeeDepartmentsPage"
                  previous-action="previousEditingEmployeeDepartmentsPage"
                  next-action="nextEditingEmployeeDepartmentsPage"
                  loading-target="setEditingEmployeeDepartmentsPage, previousEditingEmployeeDepartmentsPage, nextEditingEmployeeDepartmentsPage"
                />
              </div>
            @endif
          </div>
        </div>
      </section>

      <section class="mt-8">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Project participant') }}:</h3>

        <div class="mt-4 h-[116px] overflow-y-auto pr-1">
          <div class="grid gap-2">
            @forelse ($workspaceProjects as $project)
              <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                <input type="checkbox" wire:model="editing_employee_project_ids" value="{{ $project->id }}"
                  class="size-3.5 rounded border-slate-300 text-[#1f8fff] focus:ring-[#1f8fff]" />
                <span class="truncate">{{ $project->name }}</span>
              </label>
            @empty
              <span class="text-sm text-slate-400">{{ __('No projects') }}</span>
            @endforelse

            @if ($workspaceProjectsTotal > $this->editing_employee_projects_per_page)
              <div class="py-2">
                <x-settings.workers.partials.pagination
                  :current-page="$this->editing_employee_projects_page"
                  :total="$workspaceProjectsTotal"
                  :per-page="$this->editing_employee_projects_per_page"
                  set-page-action="setEditingEmployeeProjectsPage"
                  previous-action="previousEditingEmployeeProjectsPage"
                  next-action="nextEditingEmployeeProjectsPage"
                  loading-target="setEditingEmployeeProjectsPage, previousEditingEmployeeProjectsPage, nextEditingEmployeeProjectsPage"
                />
              </div>
            @endif
          </div>
        </div>
      </section>
    </div>

    <div class="flex h-[74px] shrink-0 items-center justify-center border-t border-zinc-200 bg-slate-50 px-6 dark:border-zinc-800 dark:bg-zinc-900">
      <flux:button variant="primary" type="submit" class="min-w-40" wire:loading.attr="disabled">
        {{ __('Save changes') }}
      </flux:button>
    </div>
  </form>
</flux:modal>
