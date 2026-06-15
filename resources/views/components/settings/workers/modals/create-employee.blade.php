
<flux:modal name="create-employee" :show="$errors->isNotEmpty()" focusable class="max-w-none"
  style="width: 440px; max-width: calc(100vw - 40px);">
  <div class="flex h-full flex-col">
    <div class="mb-8">
      <flux:heading size="lg">{{ __('Add employee') }}</flux:heading>
    </div>

    <form wire:submit="createEmployee" class="flex h-full min-h-0 flex-col mt-6">
      <div class="flex flex-col gap-4">
        <flux:input wire:model="full_name" :label="__('Full name')" type="text" required autofocus
          placeholder="{{ __('Enter full name') }}" icon-leading="user"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

        <flux:input wire:model="phone" :label="__('Phone')" type="text"
          placeholder="{{ __('Enter phone number') }}" icon-leading="phone"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

        <flux:input wire:model="email" :label="__('Email')" type="email"
          placeholder="{{ __('Enter email address') }}" icon-leading="envelope"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />

        <flux:input wire:model="position" :label="__('Position')" type="text"
          placeholder="{{ __('Enter position') }}" icon-leading="briefcase"
          class:input="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35" />
      </div>

      <div class="mt-4">
        <flux:select wire:model="employee_staff_position_id" :label="__('Staff position')"
          icon-leading="building-office"
          class="h-[42px] focus:ring-1 focus:ring-inset focus:ring-[#013763]/20 focus:border-[#013763]/35">
          <option value="">{{ __('Select staff position') }}</option>
          @foreach ($this->staffPositions as $staffPosition)
            <option value="{{ $staffPosition->id }}">
              {{ $staffPosition->department->name }} / {{ $staffPosition->name }}
            </option>
          @endforeach
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
