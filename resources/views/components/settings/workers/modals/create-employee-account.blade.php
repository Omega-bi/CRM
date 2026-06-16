<flux:modal name="create-employee-account" focusable class="max-w-none"
  style="width: 420px; max-width: calc(100vw - 28px);">
  <form wire:submit="createEmployeeAccount"
    class="-m-6 flex flex-col overflow-hidden rounded-lg bg-white text-zinc-700 dark:bg-zinc-950 dark:text-zinc-200">
    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
      <div class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
        {{ __('Create account') }}
      </div>
      <div class="mt-1 text-sm text-slate-400">
        {{ __('Authorization credentials for employee login') }}
      </div>
    </div>

    <div class="grid gap-4 px-6 py-5">
      <flux:input wire:model="account_name" :label="__('Name')" type="text"
        placeholder="{{ __('Enter full name') }}" />

      <flux:input wire:model="account_email" :label="__('Email')" type="email"
        placeholder="{{ __('Enter email address') }}" />

      <flux:input wire:model="account_password" :label="__('Password')" type="password"
        placeholder="{{ __('Enter password') }}" viewable />

      <flux:input wire:model="account_password_confirmation" :label="__('Confirm password')" type="password"
        placeholder="{{ __('Confirm password') }}" viewable />
    </div>

    <div class="flex items-center justify-end gap-2 border-t border-zinc-200 bg-slate-50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-900">
      <flux:modal.close>
        <flux:button variant="filled" type="button">{{ __('Cancel') }}</flux:button>
      </flux:modal.close>

      <flux:button variant="primary" type="submit" wire:loading.attr="disabled"
        wire:target="createEmployeeAccount">
        {{ __('Create account') }}
      </flux:button>
    </div>
  </form>
</flux:modal>
