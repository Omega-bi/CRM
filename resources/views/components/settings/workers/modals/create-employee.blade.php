<flux:modal name="create-employee" :show="$errors->isNotEmpty()" focusable class="max-w-none overflow-visible"
  style="width: 440px; max-width: calc(100vw - 40px);">
  <div class="flex h-full flex-col overflow-visible">
    <div class="mb-8">
      <flux:heading size="lg">{{ __('Add employee') }}</flux:heading>
    </div>

    <form wire:submit="createEmployee" class="mt-6 flex h-full min-h-0 flex-col overflow-visible">
      <div class="flex flex-col gap-4 overflow-visible">
        <flux:input wire:model="full_name" :label="__('Full name')" type="text" required autofocus
          placeholder="{{ __('Enter full name') }}" icon-leading="user"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

        <flux:input wire:model="phone" :label="__('Phone')" type="text" placeholder="{{ __('Enter phone number') }}"
          icon-leading="phone"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

        <flux:input wire:model="email" :label="__('Email')" type="email" placeholder="{{ __('Enter email address') }}"
          icon-leading="envelope"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

        <x-ui.select
          model="employee_department_id"
          :value="$employee_department_id"
          :label="__('Department')"
          :options="$this->departments->pluck('name', 'id')->prepend(__('Select department'), '')->all()"
          required
        />

        <x-ui.select
          wire:key="create-employee-staff-position-{{ $this->employee_department_id ?? 'all' }}"
          model="employee_staff_position_id"
          :value="$employee_staff_position_id"
          :label="__('Position')"
          :options="$this->employeeStaffPositions
            ->mapWithKeys(fn ($staffPosition) => [
              $staffPosition->id => $staffPosition->name.($this->employee_department_id === null && $staffPosition->department ? ' ('.$staffPosition->department->name.')' : ''),
            ])
            ->prepend(__('Select staff position'), '')
            ->all()"
        />
      </div>

      <div class="flex justify-end gap-2 pt-6 mt-6">
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
