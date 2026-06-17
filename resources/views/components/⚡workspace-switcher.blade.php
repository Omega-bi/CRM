<?php

use App\Data\UserWorkspace;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component {
  public function mount(): void
  {
    $user = Auth::user();

    if ($user->currentWorkspace || $user->workspaces()->doesntExist()) {
      return;
    }

    $fallbackWorkspace = $user->fallbackWorkspace();

    if ($fallbackWorkspace) {
      $user->switchWorkspace($fallbackWorkspace);
    }
  }

  public function currentWorkspace(): ?array
  {
    $workspace = Auth::user()->currentWorkspace;

    return $workspace ? [
      'id' => $workspace->id,
      'name' => $workspace->name,
      'slug' => $workspace->slug,
    ] : null;
  }

  /**
   * @return Collection<int, UserWorkspace>
   */
  public function workspaces(): Collection
  {
    return Auth::user()->toUserWorkspaces(includeCurrent: true);
  }

  public function triggerLabel(): string
  {
    return $this->currentWorkspace()['name']
      ?? $this->workspaces()->first()?->name
      ?? __('Choose workspace');
  }

  public function switchWorkspace(string $slug): void
  {
    $user = Auth::user();

    abort_unless(
      $user->belongsToWorkspace($workspace = Workspace::where('slug', $slug)->firstOrFail()),
      403
    );

    $currentWorkspaceSlug = $user->currentWorkspace?->slug;

    $user->switchWorkspace($workspace);

    if (!request()->header('Referer')) {
      $this->redirectRoute('dashboard', ['current_workspace' => $workspace->slug], navigate: true);

      return;
    }

    if (!$currentWorkspaceSlug) {
      $this->redirect(request()->header('Referer'), navigate: true);

      return;
    }

    $redirectTo = $this->replaceCurrentWorkspaceInReferer(
      request()->header('Referer'),
      $currentWorkspaceSlug,
      $workspace->slug,
    );

    $this->redirect($redirectTo ?? request()->header('Referer'), navigate: true);
  }

  protected function replaceCurrentWorkspaceInReferer(string $referer, string $currentWorkspaceSlug, string $newWorkspaceSlug): ?string
  {
    $redirectTo = preg_replace(
      '#/' . preg_quote($currentWorkspaceSlug, '#') . '(?=/|\?|$)#',
      '/' . $newWorkspaceSlug,
      $referer,
      1,
    );

    return preg_replace(
      '#([?&]current_workspace=)' . preg_quote($currentWorkspaceSlug, '#') . '(?=&|$)#',
      '$1' . $newWorkspaceSlug,
      $redirectTo ?? $referer,
      1,
    );
  }
}; ?>

<div>
  @if ($this->workspaces()->isEmpty())
    <flux:modal.trigger name="create-workspace-switcher">
      <flux:button variant="ghost" icon="plus"
        class="group w-full justify-start in-data-flux-sidebar-collapsed-desktop:justify-center"
        data-test="workspace-switcher-create-button">
        <span class="truncate font-semibold in-data-flux-sidebar-collapsed-desktop:hidden">
          {{ __('Create workspace') }}
        </span>
      </flux:button>
    </flux:modal.trigger>
  @else
    <flux:dropdown position="bottom" align="start">
      <flux:button variant="ghost"
        class="group w-full justify-start in-data-flux-sidebar-collapsed-desktop:justify-center"
        data-test="workspace-switcher-trigger">
        <flux:icon name="users" class="hidden size-4 in-data-flux-sidebar-collapsed-desktop:block" />
        <span class="truncate font-semibold in-data-flux-sidebar-collapsed-desktop:hidden">{{ $this->triggerLabel() }}</span>
        <flux:icon name="chevrons-up-down" variant="micro"
          class="ms-auto size-4 in-data-flux-sidebar-collapsed-desktop:hidden" />
      </flux:button>

      <flux:menu class="min-w-56">
        <flux:menu.heading>{{ __('Workspaces') }}</flux:menu.heading>

        @foreach ($this->workspaces() as $workspace)
          <flux:menu.item wire:click="switchWorkspace('{{ $workspace->slug }}')" class="cursor-pointer"
            data-test="workspace-switcher-item">
            <div class="flex w-full items-center justify-between">
              <span>{{ $workspace->name }}</span>
              @if ($workspace->isCurrent)
                <flux:icon name="check" class="size-4" />
              @endif
            </div>
          </flux:menu.item>
        @endforeach

        <flux:menu.separator />

        <flux:modal.trigger name="create-workspace-switcher">
          <flux:menu.item icon="plus" class="cursor-pointer" data-test="workspace-switcher-new-workspace">
            {{ __('Create workspace') }}
          </flux:menu.item>
        </flux:modal.trigger>
      </flux:menu>
    </flux:dropdown>
  @endif
</div>
