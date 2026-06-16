@php
  $departmentWorkers = $this->departmentWorkers;
@endphp

<flux:modal name="create-company-structure" focusable class="max-w-none"
  style="width: 924px; max-width: calc(100vw - 40px); height: 850px; max-height: calc(100vh - 20px);">
  <div class="flex h-full flex-col">
    <div class="flex items-center justify-between" style="margin-bottom: 34px;">
      <flux:heading size="lg">
        {{ __('New department') }}
      </flux:heading>
    </div>

    <form wire:submit="createDepartment" class="flex h-full min-h-0 flex-col">
      <div style="display: flex; gap: 24px; width: 100%; margin-bottom: 24px;">
        <div style="flex: 1 1 0; min-width: 0;">
          <flux:input wire:model="department_name" :label="__('Department name')"
            placeholder="{{ __('Enter department name') }}" required class:input="h-[42px]" />
        </div>

        <div style="flex: 1 1 0; min-width: 0;">
          <flux:select wire:model="department_parent_id" :label="__('Parent department')" class="h-[42px]">
            <option value="">{{ __('No parent department') }}</option>

            @foreach ($this->departments as $department)
              <option value="{{ $department->id }}">
                {{ $department->parent_id ? '— ' : '' }}{{ $department->name }}
              </option>
            @endforeach
          </flux:select>
        </div>
      </div>

      <div>
        <flux:input wire:model.live.debounce.300ms="department_employee_search"
          placeholder="{{ __('Search by list (name, department, position)') }}" icon-leading="magnifying-glass" />
      </div>

      <div class="mt-5 h-[520px] max-h-[calc(100vh-280px)] min-h-0 overflow-auto rounded-md border border-zinc-100 dark:border-zinc-800">
        <table class="w-full text-sm">
          <thead class="sticky top-0 z-10 bg-white dark:bg-zinc-950">
            <tr class="text-left text-[9px] text-zinc-400">
              <th class="w-10 px-4 py-3">
                {{ __('Select') }}
              </th>
              <th class="px-4 py-3 font-medium">{{ __('Name') }}</th>
              <th class="px-4 py-3 font-medium">{{ __('Department') }}</th>
              <th class="px-4 py-3 font-medium">{{ __('Position') }}</th>
              <th class="px-4 py-3 font-medium">{{ __('Role in department') }}</th>
            </tr>
          </thead>

          <tbody>
            @foreach ($departmentWorkers as $worker)
              <tr>
                <td class="px-4 py-4">
                  <input type="checkbox" wire:model="department_employee_ids" value="{{ $worker->id }}"
                    class="h-4 w-4 rounded border-zinc-300 text-[#013763] focus:ring-[#013763]" />
                </td>

                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-[10px] text-white">
                      {{ Str::of($worker->full_name ?: $worker->email)->substr(0, 1)->upper() }}
                    </div>

                    <span class="text-zinc-900 dark:text-zinc-100">
                      {{ $worker->full_name ?: $worker->email }}
                    </span>
                  </div>
                </td>

                <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">
                  {{ $worker->staffPosition?->department?->name ?: '—' }}
                </td>

                <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">
                  {{ $worker->staffPosition?->name ?: '—' }}
                </td>

                <td class="px-4 py-4 text-zinc-700 dark:text-zinc-300">
                  —
                </td>
              </tr>
            @endforeach

          </tbody>
        </table>
      </div>

      <div class="flex justify-end pt-4">
        <flux:button type="submit" variant="primary">
          {{ __('Create department') }}
        </flux:button>
      </div>
    </form>
  </div>
</flux:modal>
