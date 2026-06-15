
<flux:modal name="create-staff-position" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
  <form wire:submit="createStaffPosition" class="space-y-6">
    <div>
      <flux:heading size="lg">{{ __('Add position') }}</flux:heading>
      <flux:subheading>{{ __('Create a planned staff position inside a department.') }}</flux:subheading>
    </div>

    <flux:select wire:model="staff_position_department_id" :label="__('Department')" required
      class="focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35">
      <option value="">{{ __('Choose department') }}</option>
      @foreach ($this->departments as $department)
        <option value="{{ $department->id }}">{{ $department->name }}</option>
      @endforeach
    </flux:select>

    <flux:input wire:model="staff_position_name" :label="__('Position name')" type="text" required
      class:input="focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

    <div class="grid grid-cols-3 gap-4">
      <flux:input wire:model="staff_position_planned_count" :label="__('Planned count')" type="number" min="0"
        class:input="focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />
      <flux:input wire:model="staff_position_sort_order" :label="__('Sort order')" type="number" min="0"
        class:input="focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />
      <flux:select wire:model="staff_position_is_active" :label="__('Status')"
        class="focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35">
        <option value="1">{{ __('Active') }}</option>
        <option value="0">{{ __('Inactive') }}</option>
      </flux:select>
    </div>

    <div class="flex justify-end gap-2">
      <flux:modal.close>
        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
      </flux:modal.close>

      <flux:button variant="primary" type="submit">
        {{ __('Save') }}
      </flux:button>
    </div>
  </form>
</flux:modal>