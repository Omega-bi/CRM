@php
  $staffPositions = $this->staffPositions;
  $staffPositionsTotal = $this->staffPositionsTotal;
@endphp

<section class="mt-3 rounded-lg border border-[#e4e7ec] bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
  <div class="shrink-0 flex items-start justify-between gap-4 py-2">
    <div>
      <flux:heading size="lg" class="text-[#121b2e] font-semibold">
        {{ __('Positions') }} ({{ $staffPositionsTotal }})
      </flux:heading>
    </div>

    <flux:button variant="ghost" size="xs" icon="question-mark-circle" class="text-[#f78f08]" />
  </div>

  <div class="shrink-0 pb-3 mt-4">
    <div class="flex flex-wrap items-center gap-4 text-sm justify-between sm:flex">
      <flux:modal.trigger name="create-staff-position">
        <button type="button" class="inline-flex items-center gap-2 text-[#4a7fd9] hover:underline">
          <flux:icon name="plus-circle" class="size-4" />
          <span>{{ __('Add position') }}</span>
        </button>
      </flux:modal.trigger>

      <button type="button" class="inline-flex items-center gap-2 text-[#4a7fd9] hover:underline">
        <flux:icon name="arrow-down-tray" class="size-4" />
        <span>{{ __('Download list') }}</span>
      </button>
    </div>

    <div class="mt-4">
      <flux:input wire:model.live.debounce.300ms="staff_position_search" type="text" placeholder="{{ __('Search') }}"
        icon-leading="magnifying-glass"
        class:input="border-[#cfe2fe] focus:ring-1 focus:ring-inset focus:ring-[#cfe2fe] focus:border-[#a5c8fe]" />
    </div>
  </div>

  <div class="mt-4 max-h-[420px] overflow-auto lg:max-h-[calc(100vh-260px)]">
    <table class="w-full min-w-full table-auto">
      <thead class="sticky top-0 z-10 bg-white dark:bg-zinc-900">
        <tr class="text-left">
          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Position') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Department') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Planned count') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Occupied') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Free') }}
          </th>

          <th class="px-4 py-2 text-[10px] font-normal uppercase tracking-wide text-[#a0a8b3]">
            {{ __('Status') }}
          </th>
        </tr>
      </thead>

      <tbody>
        @forelse ($staffPositions as $staffPosition)
          @php
            $plannedCount = (int) $staffPosition->planned_count;
            $occupiedCount = (int) $staffPosition->employees_count;
            $freeCount = max(0, $plannedCount - $occupiedCount);
          @endphp

          <tr wire:click="editStaffPosition({{ $staffPosition->id }})"
            class="cursor-pointer border-t border-zinc-100 transition-colors hover:bg-[#f5f8ff] dark:border-zinc-800">
            <td class="px-4 py-3 align-middle">
              <div class="flex items-center gap-2">
                <div class="flex h-5 w-5 items-center justify-center rounded-full text-[#3498db]">
                  <flux:icon name="briefcase" class="size-5" />
                </div>

                <span class="text-sm font-normal">
                  {{ $staffPosition->name }}
                </span>
              </div>
            </td>

            <td class="px-4 py-3 align-middle text-sm">
              {{ $staffPosition->department?->name ?: __('No department assigned') }}
            </td>

            <td class="px-4 py-3 align-middle text-sm">
              {{ $plannedCount }}
            </td>

            <td class="px-4 py-3 align-middle text-sm">
              {{ $occupiedCount }}
            </td>

            <td class="px-4 py-3 align-middle text-sm">
              {{ $freeCount }}
            </td>

            <td class="px-4 py-3 align-middle text-sm">
              {{ $staffPosition->is_active ? __('Active') : __('Inactive') }}
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="px-4 py-8 text-center text-sm text-[#a0a8b3]">
              {{ __('No positions yet') }}
            </td>
          </tr>
        @endforelse

        @if ($staffPositionsTotal > $this->staff_positions_per_page)
          <tr>
            <td colspan="6" class="px-4 py-4">
              <x-settings.workers.partials.pagination
                :current-page="$this->staff_positions_page"
                :total="$staffPositionsTotal"
                :per-page="$this->staff_positions_per_page"
                set-page-action="setStaffPositionsPage"
                previous-action="previousStaffPositionsPage"
                next-action="nextStaffPositionsPage"
                loading-target="setStaffPositionsPage, previousStaffPositionsPage, nextStaffPositionsPage"
              />
            </td>
          </tr>
        @endif
      </tbody>
    </table>
  </div>
</section>
