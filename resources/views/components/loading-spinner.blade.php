@props([
  'target',
  'label' => __('Loading'),
])

<div
  wire:loading.delay.longer.flex
  wire:target="{{ $target }}"
  {{ $attributes->class('items-center justify-center text-[#1f8fff] dark:text-[#60a5fa]') }}
>
  <flux:icon name="arrow-path" class="size-4 animate-spin" aria-hidden="true" />

  <span class="sr-only">{{ $label }}</span>
</div>
