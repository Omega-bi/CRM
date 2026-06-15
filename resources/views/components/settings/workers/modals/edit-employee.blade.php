<flux:modal name="edit-employee" focusable class="max-w-none"
  style="width: 760px; max-width: calc(100vw - 40px);">
  <div class="flex h-full flex-col">
    <div class="mb-6">
      <flux:heading size="lg">{{ __('Edit employee') }}</flux:heading>
    </div>

    <form wire:submit="updateEmployee" class="flex h-full min-h-0 flex-col gap-6">
      <div class="grid gap-6 md:grid-cols-[220px_minmax(0,1fr)]">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/60">
          <div class="flex flex-col items-center gap-3 text-center">
            <flux:avatar :name="$this->editing_employee_full_name" size="xl" />

            <div>
              <div class="text-sm font-semibold text-zinc-950 dark:text-zinc-50">
                {{ $this->editing_employee_full_name }}
              </div>

              <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $this->editing_employee_email ?: '—' }}
              </div>
            </div>
          </div>

          <div class="mt-5 space-y-3 text-sm">
            <div>
              <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('Photo') }}
              </div>
              <div class="mt-1 text-zinc-700 dark:text-zinc-200">
                {{ __('Avatar preview') }}
              </div>
            </div>

            <div>
              <div class="text-[11px] uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                {{ __('Projects') }}
              </div>
              <div class="mt-2 grid gap-2">
                @forelse ($this->workspaceProjects as $project)
                  <label class="flex items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    <input type="checkbox" wire:model="editing_employee_project_ids" value="{{ $project->id }}"
                      class="rounded border-zinc-300 text-[#013763] focus:ring-[#013763]" />
                    <span class="truncate">{{ $project->name }}</span>
                  </label>
                @empty
                  <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No projects') }}</span>
                @endforelse
              </div>
            </div>
          </div>
        </div>

        <div class="grid gap-4">
          <flux:input wire:model="editing_employee_full_name" :label="__('Full name')" type="text" required
            placeholder="{{ __('Enter full name') }}" icon-leading="user"
            class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

          <flux:input wire:model="editing_employee_phone" :label="__('Phone')" type="text"
            placeholder="{{ __('Enter phone number') }}" icon-leading="phone"
            class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

          <flux:input wire:model="editing_employee_email" :label="__('Email')" type="email"
            placeholder="{{ __('Enter email address') }}" icon-leading="envelope"
            class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

          <flux:input wire:model="editing_employee_position" :label="__('Position')" type="text"
            placeholder="{{ __('Enter position') }}" icon-leading="briefcase"
            class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

          <flux:select wire:model.live="editing_employee_department_id" :label="__('Department')" required
            icon-leading="building-office"
            class="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35">
            <option value="">{{ __('Select department') }}</option>
            @foreach ($this->departments as $department)
              <option value="{{ $department->id }}">
                {{ $department->name }}
              </option>
            @endforeach
          </flux:select>

          <flux:select wire:model="editing_employee_staff_position_id" :label="__('Role in department')"
            icon-leading="clipboard-document-list"
            class="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35">
            <option value="">{{ __('Select position') }}</option>
            @foreach ($this->editingEmployeeStaffPositions as $staffPosition)
              <option value="{{ $staffPosition->id }}">
                {{ $staffPosition->name }}
              </option>
            @endforeach
          </flux:select>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <flux:modal.close>
          <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
        </flux:modal.close>

        <flux:button variant="primary" type="submit">
          {{ __('Save') }}
        </flux:button>
      </div>
    </form>
  </div>
</flux:modal>
