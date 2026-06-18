<?php

use Modules\Workspace\DTO\UserWorkspace;
use Modules\Workspace\Models\Workspace;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Workspace $workspace;

    public string $deleteName = '';

    public function mount(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    #[Computed]
    public function deleteConfirmLabel(): string
    {
        return __('Type ":name" to confirm', ['name' => $this->workspace->name]);
    }

    public function deleteWorkspace(): void
    {
        Gate::authorize('delete', $this->workspace);

        $validated = $this->validate([
            'deleteName' => ['required', 'string'],
        ]);

        if ($validated['deleteName'] !== $this->workspace->name) {
            $this->addError('deleteName', __('The workspace name does not match.'));

            return;
        }

        $user = Auth::user();

        $fallbackWorkspace = $user->isCurrentWorkspace($this->workspace)
            ? $user->fallbackWorkspace($this->workspace)
            : null;

        DB::transaction(function () use ($user) {
            User::where('current_workspace_id', $this->workspace->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchWorkspace($affectedUser->personalWorkspace()));

            $this->workspace->invitations()->delete();
            $this->workspace->memberships()->delete();
            $this->workspace->delete();
        });

        if ($fallbackWorkspace) {
            $user->switchWorkspace($fallbackWorkspace);
        }

        Flux::toast(variant: 'success', text: __('Workspace deleted.'));

        $this->redirectRoute('workspaces.index', navigate: true);
    }

    /**
     * @return Collection<int, UserWorkspace>
     */
    #[Computed]
    public function otherWorkspaces(): Collection
    {
        return Auth::user()->toUserWorkspaces();
    }
}; ?>

<flux:modal name="delete-workspace" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form wire:submit="deleteWorkspace" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Are you sure?') }}</flux:heading>
            <flux:subheading>
                {{ __('This action cannot be undone. This will permanently delete the workspace ":name".', ['name' => $workspace->name]) }}
            </flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="deleteName" :label="$this->deleteConfirmLabel" required data-test="delete-workspace-name" />
        </div>

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" type="submit" data-test="delete-workspace-confirm">
                {{ __('Delete workspace') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
