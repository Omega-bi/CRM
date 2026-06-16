@props([
  'currentPage',
  'total',
  'perPage',
  'setPageAction',
  'previousAction',
  'nextAction',
  'loadingTarget',
])

@php
  $lastPage = max(1, (int) ceil($total / $perPage));
  $startPage = max(1, min($currentPage - 1, $lastPage - 3));
  $endPage = min($lastPage, $startPage + 3);
  $startPage = max(1, $endPage - 3);
@endphp

@if ($lastPage > 1)
  <nav class="flex flex-wrap items-center justify-center gap-1.5 text-xs" aria-label="Pagination">
    <button type="button" wire:click="{{ $previousAction }}" wire:loading.attr="disabled" wire:target="{{ $loadingTarget }}"
      @disabled($currentPage <= 1)
      class="h-7 rounded border border-slate-200 px-2.5 text-slate-600 transition hover:border-[#1f8fff] hover:text-[#1f8fff] disabled:pointer-events-none disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300">
      Назад
    </button>

    @for ($page = $startPage; $page <= $endPage; $page++)
      <button type="button" wire:click="{{ $setPageAction }}({{ $page }})" wire:loading.attr="disabled"
        wire:target="{{ $loadingTarget }}"
        class="{{ $page === $currentPage ? 'border-[#1f8fff] bg-[#1f8fff] text-white' : 'border-slate-200 text-slate-600 hover:border-[#1f8fff] hover:text-[#1f8fff] dark:border-zinc-700 dark:text-zinc-300' }} h-7 min-w-7 rounded border px-2 transition disabled:pointer-events-none disabled:opacity-60">
        {{ $page }}
      </button>
    @endfor

    <button type="button" wire:click="{{ $nextAction }}" wire:loading.attr="disabled" wire:target="{{ $loadingTarget }}"
      @disabled($currentPage >= $lastPage)
      class="h-7 rounded border border-slate-200 px-2.5 text-slate-600 transition hover:border-[#1f8fff] hover:text-[#1f8fff] disabled:pointer-events-none disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300">
      Вперед
    </button>
  </nav>
@endif
