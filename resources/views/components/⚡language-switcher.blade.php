<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
  public string $locale = 'en';

  /**
   * @var array<string, string>
   */
  public array $locales = [
    'en' => 'EN',
    'ru' => 'RU',
  ];

  public function mount(): void
  {
    $this->locale = Auth::user()->locale ?? app()->getLocale();
  }

  public function switchLocale(string $locale): void
  {
    $validated = validator(
      ['locale' => $locale],
      ['locale' => ['required', 'string', Rule::in(array_keys($this->locales))]],
    )->validate();

    $user = Auth::user();
    $user->forceFill(['locale' => $validated['locale']])->save();

    app()->setLocale($validated['locale']);

    $this->redirect(request()->header('Referer') ?: url()->current(), navigate: true);
  }
}; ?>

<flux:dropdown position="bottom" align="end">
  <button type="button"
    class="inline-flex h-12 cursor-pointer items-center gap-1 px-3 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-950 dark:hover:text-white"
    data-test="language-switcher-trigger">
    <span>{{ $locales[$locale] ?? strtoupper($locale) }}</span>
    <flux:icon.chevron-down class="size-4" />
  </button>

  <flux:menu class="min-w-28">
    @foreach ($locales as $code => $label)
      <flux:menu.item wire:click="switchLocale('{{ $code }}')" class="cursor-pointer" data-test="language-switcher-item">
        <div class="flex w-full items-center justify-between gap-3">
          <span>{{ $label }}</span>

          @if ($locale === $code)
            <flux:icon.check class="size-4" />
          @endif
        </div>
      </flux:menu.item>
    @endforeach
  </flux:menu>
</flux:dropdown>
