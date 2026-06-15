<section class="mt-3 rounded-lg border border-[#e4e7ec] bg-white p-4">
  <div class="flex items-start justify-between gap-4 py-2">
    <div>
      <flux:heading size="lg" class="text-[#121b2e] font-semibold">{{ __('Company staff') }}
        ({{ $this->workers->count() }})</flux:heading>
    </div>

    <flux:button variant="ghost" size="xs" icon="question-mark-circle" class="text-[#f78f08]" />
  </div>

  <div class="pb-3 mt-4">
    <div class="flex flex-wrap items-center gap-4 text-sm justify-between sm:flex">
      <flux:modal.trigger name="create-employee">
        <button type="button" class="inline-flex items-center gap-2 text-[#4a7fd9] hover:underline">
          <flux:icon name="user-plus" class="size-4" />
          <span>{{ __('Add employee') }}</span>
        </button>
      </flux:modal.trigger>

      <div class="flex items-center gap-4">
        <button type="button" class="inline-flex items-center gap-2 text-[#4a7fd9] hover:underline">
          <flux:icon name="arrow-down-tray" class="size-4" />
          <span>{{ __('Roles by projects') }}</span>
        </button>

        <button type="button" class="inline-flex items-center gap-2 text-[#4a7fd9] hover:underline">
          <flux:icon name="arrow-down-tray" class="size-4" />
          <span>{{ __('Download list') }}</span>
        </button>
      </div>
    </div>

    <div class="mt-4">
      <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search') }}"
        icon-leading="magnifying-glass"
        class:input="border-[#cfe2fe] focus:ring-1 focus:ring-inset focus:ring-[#cfe2fe] focus:border-[#a5c8fe]" />
    </div>
  </div>

  <div class="mt-4 overflow-x-auto">
    <table class="w-full min-w-full table-auto">
      <thead>
        <tr class="text-left">
          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Name') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('E-mail') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Position') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Department') }}
          </th>
        </tr>
      </thead>

      <tbody>
      @forelse ($this->workers as $worker)
        @php
          $displayName = $worker->full_name ?: ($worker->user?->email ?: $worker->email);
          $displayEmail = $worker->email ?: ($worker->user?->email ?: '—');
          $displayPosition = $worker->staffPosition?->name ?: ($worker->position ?: '');
          $displayDepartment = $worker->departments->first()?->name ?: $worker->staffPosition?->department?->name ?: '';
        @endphp

          <tr wire:click="editEmployee({{ $worker->id }})" class="cursor-pointer transition-colors hover:bg-[#f5f8ff]">
            <td class="px-4 py-3 align-middle">
              <div class="flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full text-[#3498db]">
                  <flux:icon name="user-circle" class="size-5" />
                </div>

                <span class="text-xs font-normal text-[#a2a7ba]">
                  {{ $displayName }}
                </span>

                @if ($worker->user_id === auth()->id())
                  <flux:icon name="sparkles" class="size-3.5 text-[#f78f08]" />
                @endif
              </div>
            </td>

            <td class="px-4 py-3 align-middle text-xs font-normal text-[#a5abb8]">
              {{ $displayEmail }}
            </td>

            <td class="px-4 py-3 align-middle text-xs text-[#a5abb8]">
              {{ $displayPosition }}
            </td>

            <td class="px-4 py-3 align-middle text-xs text-[#9ea5ba]">
              {{ $displayDepartment }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="px-4 py-8 text-center text-xs text-[#a0a8b3]">
              {{ __('No workers yet') }}
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
