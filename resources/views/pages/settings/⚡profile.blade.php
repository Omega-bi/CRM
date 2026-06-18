<?php

use App\Concerns\ProfileValidationRules;
/* @chisel-email-verification */
use Illuminate\Contracts\Auth\MustVerifyEmail;
/* @end-chisel-email-verification */
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
  use ProfileValidationRules, WithFileUploads;

  public string $name = '';
  public string $email = '';
  public string $locale = 'en';
  /** @var array<int, string> */
  public array $phoneNumbers = [''];
  public ?TemporaryUploadedFile $profilePhoto = null;

  public function mount(): void
  {
    $user = Auth::user();

    $this->name = $user->name;
    $this->email = $user->email;
    $this->locale = $user->locale ?? config('app.locale');
    $this->phoneNumbers = $this->phoneNumbersFor($user) ?: [''];
  }

  public function updateProfileInformation(): void
  {
    $user = Auth::user();
    $validated = $this->validate($this->profileRules($user->id));
    $user->fill($validated);

    if ($user->isDirty('email')) {
      $user->email_verified_at = null;
    }

    $user->save();

    app()->setLocale($validated['locale'] ?? config('app.locale'));

    Flux::toast(variant: 'success', text: __('Profile updated.'));
  }

  public function updatedProfilePhoto(): void
  {
    $validated = $this->validate([
      'profilePhoto' => ['required', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(2048)],
    ]);

    $user = Auth::user();
    $previousPath = $user->profile_photo_path;
    $path = $validated['profilePhoto']->storePublicly(path: 'profile-photos', options: 'public');

    $user->profile_photo_path = $path;
    $user->save();

    if ($previousPath) {
      Storage::disk('public')->delete($previousPath);
    }

    $this->profilePhoto = null;

    Flux::toast(variant: 'success', text: __('Profile photo updated.'));
  }

  public function openPhoneModal(): void
  {
    $this->resetValidation();
    $this->phoneNumbers = $this->phoneNumbersFor(Auth::user()) ?: [''];
  }

  public function addPhoneNumberField(): void
  {
    $this->phoneNumbers[] = '';
  }

  public function removePhoneNumberField(int $index): void
  {
    unset($this->phoneNumbers[$index]);

    $this->phoneNumbers = array_values($this->phoneNumbers) ?: [''];
  }

  public function savePhoneNumbers(): void
  {
    $validated = $this->validate([
      'phoneNumbers' => ['array', 'max:5'],
      'phoneNumbers.*' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\s().-]+$/'],
    ], [
      'phoneNumbers.*.regex' => __('Use phone format +7 (701) 123-45-67.'),
    ]);

    $normalizedPhoneNumbers = $this->normalizePhoneNumbers($validated['phoneNumbers']);

    /** @var \App\Models\User $user */
    $user = Auth::user();
    $user->phone_numbers = $normalizedPhoneNumbers ?: null;
    $user->save();

    $this->phoneNumbers = $normalizedPhoneNumbers ?: [''];
    unset($this->profilePhones);

    $this->dispatch('close-modal', name: 'profile-phone-modal');

    Flux::toast(variant: 'success', text: __('Phone numbers updated.'));
  }

  /* @chisel-email-verification */
  public function resendVerificationNotification(): void
  {
    $user = Auth::user();

    if ($user->hasVerifiedEmail()) {
      $this->redirectIntended(default: route('dashboard', absolute: false));
      return;
    }

    $user->sendEmailVerificationNotification();
    Session::flash('status', 'verification-link-sent');
  }

  #[Computed]
  public function hasUnverifiedEmail(): bool
  {
    return Auth::user() instanceof MustVerifyEmail && !Auth::user()->hasVerifiedEmail();
  }

  #[Computed]
  public function showDeleteUser(): bool
  {
    return !Auth::user() instanceof MustVerifyEmail
      || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
  }

  #[Computed]
  public function profilePhones(): array
  {
    return $this->phoneNumbersFor(Auth::user());
  }
  /* @end-chisel-email-verification */

  private function phoneNumbersFor(mixed $user): array
  {
    if (is_array($user->phone_numbers) && $user->phone_numbers !== []) {
      return collect($user->phone_numbers)
        ->filter(fn (mixed $phoneNumber): bool => is_string($phoneNumber) && trim($phoneNumber) !== '')
        ->map(fn (string $phoneNumber): string => $this->formatPhoneNumber($phoneNumber) ?? trim($phoneNumber))
        ->values()
        ->all();
    }

    return $this->splitPhoneNumbers($user->employee?->phone ?? $user->customerContact?->phone);
  }

  private function splitPhoneNumbers(?string $phoneNumbers): array
  {
    if (!filled($phoneNumbers)) {
      return [];
    }

    return collect(preg_split('/\r\n|\r|\n|[,;]/', $phoneNumbers) ?: [])
      ->map(fn (string $phoneNumber): string => trim($phoneNumber))
      ->filter()
      ->map(fn (string $phoneNumber): string => $this->formatPhoneNumber($phoneNumber) ?? $phoneNumber)
      ->values()
      ->all();
  }

  private function normalizePhoneNumbers(array $phoneNumbers): array
  {
    $normalizedPhoneNumbers = [];

    foreach ($phoneNumbers as $index => $phoneNumber) {
      $phoneNumber = is_string($phoneNumber) ? trim($phoneNumber) : '';

      if ($phoneNumber === '') {
        continue;
      }

      $formattedPhoneNumber = $this->formatPhoneNumber($phoneNumber);

      if ($formattedPhoneNumber === null) {
        throw ValidationException::withMessages([
          "phoneNumbers.{$index}" => __('Use phone format +7 (701) 123-45-67.'),
        ]);
      }

      $normalizedPhoneNumbers[] = $formattedPhoneNumber;
    }

    return array_values(array_unique($normalizedPhoneNumbers));
  }

  private function formatPhoneNumber(string $phoneNumber): ?string
  {
    $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

    if (strlen($digits) === 10) {
      $digits = '7' . $digits;
    }

    if (strlen($digits) !== 11 || !in_array($digits[0], ['7', '8'], true)) {
      return null;
    }

    return sprintf(
      '+7 (%s) %s-%s-%s',
      substr($digits, 1, 3),
      substr($digits, 4, 3),
      substr($digits, 7, 2),
      substr($digits, 9, 2),
    );
  }
}; ?>

<section class="w-full">
  @include('partials.settings-heading')

  <flux:heading class="sr-only">{{ __('Profile settings') }}</flux:heading>

  <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Manage your personal information and account settings')" :full-width="true">
    @php
      $user = Auth::user();
      $profilePosition = $user->employee?->position ?? $user->customerContact?->position;
      $profilePhotoUrl = $user->profile_photo_path ? Storage::disk('public')->url($user->profile_photo_path) : null;
      $languageOptions = [
        'en' => __('English'),
        'ru' => __('Russian'),
      ];

      $appearanceOptions = [
        'light' => __('Light'),
        'dark' => __('Dark'),
        'system' => __('System'),
      ];
    @endphp

    <div x-data="{
                notifications: true,
                appearanceLabels: @js($appearanceOptions),
            }" class="mt-4 w-full max-w-none">
      <form wire:submit="updateProfileInformation" class="grid w-full gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
        <aside class="min-w-0 rounded-md border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
          <label
            class="group relative flex aspect-square w-full cursor-pointer items-center justify-center overflow-hidden rounded-md bg-zinc-50 text-zinc-400 dark:bg-zinc-900">
            @if ($profilePhotoUrl)
              <img src="{{ $profilePhotoUrl }}" alt="{{ __('Profile photo') }}" class="size-full object-cover">
            @else
              <span class="flex flex-col items-center gap-2 text-zinc-500 dark:text-zinc-400">
                <flux:icon name="photo" class="size-9 !text-zinc-400 dark:!text-zinc-500" />
                <span class="text-sm font-medium">{{ __('Photo') }}</span>
              </span>
            @endif

            <span
              class="absolute inset-x-0 bottom-0 bg-black/55 px-3 py-2 text-center text-xs font-medium text-white opacity-0 transition group-hover:opacity-100">
              {{ __('Upload photo') }}
            </span>

            <input wire:model="profilePhoto" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only">
          </label>

          <div class="mt-2 min-h-5">
            <p wire:loading wire:target="profilePhoto" class="text-xs text-zinc-500">
              {{ __('Loading') }}
            </p>

            <div wire:loading.remove wire:target="profilePhoto">
              @error('profilePhoto')
                <p class="text-xs text-red-600">{{ $message }}</p>
              @enderror
            </div>
          </div>
        </aside>

        <section class="min-w-0 rounded-md border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-950">
          <div class="mb-4 flex items-center justify-between gap-3">
            <div class="min-w-0">
              <flux:heading size="lg">{{ __('Personal information') }}</flux:heading>
              <flux:text class="text-xs">{{ __('Manage your personal information and account settings') }}</flux:text>
            </div>
          </div>

          <div class="space-y-3">
            <div
              class="grid min-h-12 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Full name') }}</span>
              <span class="flex w-full max-w-[360px] items-center justify-end gap-1.5 justify-self-end">
                <input wire:model="name" type="text" required autocomplete="name"
                  class="min-w-0 flex-1 !border-0 !bg-transparent !px-0 py-1.5 text-right text-sm text-zinc-900 !shadow-none outline-none transition focus:!border-transparent focus:!ring-0 dark:!bg-transparent dark:text-zinc-100">
                <flux:icon name="pencil" class="size-3.5 shrink-0 !text-zinc-500 dark:!text-zinc-400" />
              </span>
            </div>

            <div
              class="grid min-h-12 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Email') }}</span>
              <span class="flex w-full max-w-[360px] items-center justify-end gap-1.5 justify-self-end">
                <input wire:model="email" type="email" required autocomplete="email"
                  class="min-w-0 flex-1 !border-0 !bg-transparent !px-0 py-1.5 text-right text-sm text-zinc-900 !shadow-none outline-none transition focus:!border-transparent focus:!ring-0 dark:!bg-transparent dark:text-zinc-100">
                <flux:icon name="pencil" class="size-3.5 shrink-0 !text-zinc-500 dark:!text-zinc-400" />
              </span>
            </div>

            <div
              class="grid min-h-10 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Phone') }}</span>
              <flux:modal.trigger name="profile-phone-modal">
                <button type="button" wire:click="openPhoneModal"
                  class="inline-flex max-w-[360px] cursor-pointer items-center justify-end gap-1.5 justify-self-end text-right text-sm text-zinc-900 hover:text-[#006de5] dark:text-zinc-100 dark:hover:text-[#8dc5ff]">
                  @if ($this->profilePhones === [])
                    <span class="text-[#006de5]">{{ __('Add') }}</span>
                  @else
                    <span class="flex flex-col items-end gap-0.5">
                      @foreach ($this->profilePhones as $profilePhone)
                        <span wire:key="profile-phone-value-{{ $loop->index }}">{{ $profilePhone }}</span>
                      @endforeach
                    </span>
                    <flux:icon name="pencil" class="size-3.5 shrink-0 !text-zinc-500 dark:!text-zinc-400" />
                  @endif
                </button>
              </flux:modal.trigger>
            </div>

            <div
              class="grid min-h-10 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Position') }}</span>
              <span
                class="justify-self-end text-right text-zinc-900 dark:text-zinc-100">{{ filled($profilePosition) ? $profilePosition : __('Not set') }}</span>
            </div>

            <div
              class="grid min-h-10 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Email verification') }}</span>
              <span
                class="justify-self-end text-right text-zinc-900 dark:text-zinc-100">{{ $this->hasUnverifiedEmail ? __('Not verified') : __('Verified') }}</span>
            </div>

            <div
              class="grid min-h-10 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Notifications') }}</span>
              <button type="button" @click="notifications = ! notifications"
                class="inline-flex h-6 w-11 items-center justify-self-end rounded-full bg-zinc-300 p-0.5 transition dark:bg-zinc-700"
                :class="notifications ? 'bg-[#006de5] dark:bg-[#006de5]' : ''" :aria-pressed="notifications.toString()">
                <span class="size-5 rounded-full bg-white shadow-sm transition"
                  :class="notifications ? 'translate-x-5' : 'translate-x-0'"></span>
              </button>
            </div>

            <div
              class="grid min-h-10 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Language') }}</span>
              <flux:dropdown position="bottom" align="end" class="justify-self-end">
                <button type="button" class="inline-flex cursor-pointer items-center justify-end gap-2 text-[#006de5]">
                  <span>{{ $languageOptions[$locale] ?? strtoupper($locale) }}</span>
                  <flux:icon.chevron-down class="size-4" />
                </button>
                <flux:menu class="min-w-40">
                  @foreach ($languageOptions as $code => $label)
                    <flux:menu.item wire:key="profile-locale-menu-{{ $code }}" wire:click="$set('locale', '{{ $code }}')"
                      class="cursor-pointer">
                      {{ $label }}
                    </flux:menu.item>
                  @endforeach
                </flux:menu>
              </flux:dropdown>
            </div>

            <div
              class="grid min-h-10 items-center gap-3 rounded-md bg-zinc-50 px-4 py-2 text-sm dark:bg-zinc-900 sm:grid-cols-[170px_minmax(0,1fr)]">
              <span class="text-zinc-700 dark:text-zinc-300">{{ __('Appearance') }}</span>
              <flux:dropdown position="bottom" align="end" class="justify-self-end">
                <button type="button" class="inline-flex cursor-pointer items-center justify-end gap-2 text-[#006de5]">
                  <span x-text="appearanceLabels[$flux.appearance] ?? appearanceLabels.system"></span>
                  <flux:icon.chevron-down class="size-4" />
                </button>
                <flux:menu class="min-w-40">
                  @foreach ($appearanceOptions as $code => $label)
                    <flux:menu.item x-on:click="$flux.appearance = '{{ $code }}'" class="cursor-pointer">
                      {{ $label }}
                    </flux:menu.item>
                  @endforeach
                </flux:menu>
              </flux:dropdown>
            </div>
          </div>

          {{-- @chisel-email-verification --}}
          @if ($this->hasUnverifiedEmail)
            <div
              class="mt-4 flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-700 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-400">
              <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
              <span>
                {{ __('Your email address is unverified.') }}
                <button type="button" wire:click.prevent="resendVerificationNotification"
                  class="ml-1 underline hover:no-underline">
                  {{ __('Resend verification email.') }}
                </button>
              </span>
            </div>

            @if (session('status') === 'verification-link-sent')
              <p class="mt-2 text-xs text-green-600">{{ __('A new verification link has been sent.') }}</p>
            @endif
          @endif
          {{-- @end-chisel-email-verification --}}

          <div class="mt-4 flex justify-end">
            <flux:button type="submit" size="sm" variant="primary" data-test="update-profile-button">
              {{ __('Save changes') }}
            </flux:button>
          </div>
        </section>
      </form>
    </div>

    <flux:modal name="profile-phone-modal" :show="$errors->has('phoneNumbers') || $errors->has('phoneNumbers.*')" focusable class="w-[420px] max-w-[calc(100vw-2rem)]">
      <form wire:submit="savePhoneNumbers" class="space-y-5">
        <div class="space-y-1">
          <flux:heading size="lg">{{ __('Contact numbers') }}</flux:heading>
        </div>

        <div class="space-y-3">
          @foreach ($phoneNumbers as $index => $phoneNumber)
            <div wire:key="profile-phone-input-{{ $index }}" class="w-full">
              <flux:input wire:model="phoneNumbers.{{ $index }}" :label="$index === 0 ? __('Phone') : null"
                placeholder="+7 (701) 123-45-67" required class="w-full">
                <x-slot name="iconTrailing">
                  <flux:button type="button" size="sm" variant="subtle" icon="trash" tooltip="{{ __('Remove phone') }}"
                    wire:click="removePhoneNumberField({{ $index }})" class="-mr-1" />
                </x-slot>
              </flux:input>
            </div>
          @endforeach

          <button type="button" wire:click="addPhoneNumberField"
            class="inline-flex h-8 cursor-pointer items-center text-sm font-medium text-[#006de5] hover:underline">
            {{ __('Add another phone') }}
          </button>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
          <flux:modal.close>
            <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
          </flux:modal.close>

          <flux:button type="submit" size="sm" variant="primary" data-test="save-profile-phones">
            {{ __('Save changes') }}
          </flux:button>
        </div>
      </form>
    </flux:modal>

    {{-- @chisel-email-verification --}}
    @if ($this->showDeleteUser)
      {{-- @end-chisel-email-verification --}}
      <div class="mt-4">
        <livewire:pages::settings.delete-user-form />
      </div>
      {{-- @chisel-email-verification --}}
    @endif
    {{-- @end-chisel-email-verification --}}
  </x-pages::settings.layout>
</section>
