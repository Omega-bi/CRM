@props([
  'model',
  'value' => null,
  'label' => null,
  'options' => [],
  'placeholder' => null,
  'required' => false,
  'disabled' => false,
  'size' => 'base',
])

@php
  $normalizedOptions = collect($options)
    ->map(fn ($label, $value) => ['value' => (string) $value, 'label' => (string) $label])
    ->values()
    ->all();

  $currentValue = (string) ($value ?? '');
  $buttonHeight = match ($size) {
    'xs' => 'h-7 text-xs',
    'sm' => 'h-8 text-sm',
    default => 'h-9 text-sm',
  };
@endphp

<div
  x-data="{
    open: false,
    value: @js($currentValue),
    options: @js($normalizedOptions),
    placeholder: @js($placeholder),
    selectedLabel() {
      return this.options.find((option) => option.value === String(this.value))?.label || this.placeholder || '';
    },
    select(option) {
      this.value = option.value;
      this.open = false;
      this.$refs.button.focus();
    },
  }"
  x-on:keydown.escape.window="open = false"
  {{ $attributes->class('relative grid gap-2') }}
  x-bind:class="open ? 'z-[1000]' : 'z-0'"
>
  @if ($label)
    <label class="text-sm font-medium leading-tight text-zinc-800 dark:text-zinc-100">
      {{ $label }}@if ($required)<span class="text-red-500">*</span>@endif
    </label>
  @endif

  <div class="relative overflow-visible">
    <button
      x-ref="button"
      type="button"
      x-on:click="open = !open"
      x-on:keydown.arrow-down.prevent="open = true"
      x-on:keydown.enter.prevent="open = !open"
      x-bind:aria-expanded="open"
      @disabled($disabled)
      class="{{ $buttonHeight }} flex w-full cursor-pointer items-center justify-between gap-3 rounded-md border border-zinc-300 bg-white px-3 text-left text-zinc-900 shadow-none transition disabled:cursor-not-allowed disabled:bg-zinc-50 disabled:text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:disabled:bg-zinc-800"
      :class="open ? 'border-[var(--color-brand-primary)] ring-1 ring-[color-mix(in_oklab,var(--color-brand-primary),transparent_72%)]' : 'hover:border-[#a5c8fe]'"
      role="combobox"
      aria-haspopup="listbox"
    >
      <span class="block truncate" x-text="selectedLabel()"></span>
      <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-500 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
    </button>

    <div
      x-cloak
      x-show="open"
      x-transition.origin.top.left
      x-on:click.outside="open = false"
      class="absolute z-[1000] mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
      role="listbox"
    >
      <template x-for="option in options" :key="option.value">
        <button
          type="button"
          x-on:click="select(option); $wire.set(@js($model), option.value)"
          class="flex w-full cursor-pointer items-center justify-between gap-3 rounded px-2.5 py-2 text-left text-sm text-zinc-800 hover:bg-zinc-50 hover:text-[var(--color-brand-primary)] dark:text-zinc-100 dark:hover:bg-zinc-800"
          x-bind:aria-selected="String(value) === option.value"
          role="option"
        >
          <span class="truncate" x-text="option.label"></span>
          <flux:icon.check x-show="String(value) === option.value" class="size-4 shrink-0 text-[var(--color-brand-primary)]" />
        </button>
      </template>
    </div>
  </div>
</div>
