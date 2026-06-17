
<flux:modal name="create-department" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
  <form wire:submit="createDepartment" class="space-y-6">
    <div>
      <flux:heading size="lg">{{ __('Add department') }}</flux:heading>
      <flux:subheading>{{ __('Create a company department.') }}</flux:subheading>
    </div>

    <flux:input wire:model="department_name" :label="__('Department name')" type="text" required autofocus
      class:input="focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

    <div class="grid grid-cols-2 gap-4">
      <flux:input wire:model="department_sort_order" :label="__('Sort order')" type="number" min="0"
        class:input="focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />
      <x-ui.select
        model="department_is_active"
        :value="$department_is_active"
        :label="__('Status')"
        :options="[
          '1' => __('Active'),
          '0' => __('Inactive'),
        ]"
      />
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
