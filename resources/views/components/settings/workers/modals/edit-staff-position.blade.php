<flux:modal name="edit-staff-position" :show="$errors->isNotEmpty()" focusable class="max-w-none"
  style="width: 440px; max-width: calc(100vw - 40px);">
  <div class="flex h-full flex-col">
    <div class="mb-8">
      <flux:heading size="lg">{{ __('Edit position') }}</flux:heading>
      <flux:subheading>{{ __('Create a staff position inside a department.') }}</flux:subheading>
    </div>

    <form wire:submit="updateStaffPosition" class="flex h-full min-h-0 flex-col">
      <div class="flex flex-col gap-4">
        <flux:select wire:model="editing_staff_position_department_id" :label="__('Department')" required
          icon-leading="building-office"
          class="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35">
          <option value="">{{ __('Select department') }}</option>
          @foreach ($this->departments as $department)
            <option value="{{ $department->id }}">{{ $department->name }}</option>
          @endforeach
        </flux:select>

        <flux:input wire:model="editing_staff_position_name" :label="__('Position name')" type="text" required
          icon-leading="briefcase"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

        <flux:select wire:model="editing_staff_position_is_active" :label="__('Status')"
          class="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35">
          <option value="1">{{ __('Active') }}</option>
          <option value="0">{{ __('Inactive') }}</option>
        </flux:select>
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
