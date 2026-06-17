<?php

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Notifications\Workspaces\WorkspaceInvitation as WorkspaceInvitationNotification;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Workspace\Rules\UniqueWorkspaceInvitation;

new class extends Component {
    public Workspace $workspace;

    public string $inviteEmail = '';

    public string $inviteRole = 'member';

    public function mount(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function createInvitation(): void
    {
        Gate::authorize('inviteMember', $this->workspace);

        $validated = $this->validate([
            'inviteEmail' => ['required', 'string', 'email', 'max:255', new UniqueWorkspaceInvitation($this->workspace)],
            'inviteRole' => ['required', 'string', Rule::enum(WorkspaceRole::class)],
        ]);

        $invitation = $this->workspace->invitations()->create([
            'email' => $validated['inviteEmail'],
            'role' => WorkspaceRole::from($validated['inviteRole']),
            'invited_by' => Auth::id(),
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new WorkspaceInvitationNotification($invitation));

        $this->reset('inviteEmail', 'inviteRole');
        $this->dispatch('close-modal', name: 'invite-member');

        Flux::toast(variant: 'success', text: __('Invitation sent.'));

        $this->redirectRoute('workspaces.edit', ['workspace' => $this->workspace->slug], navigate: true);
    }

    #[Computed]
    public function availableRoles(): array
    {
        return WorkspaceRole::assignable();
    }
}; ?>

<flux:modal name="invite-member" :show="$errors->isNotEmpty()" focusable class="max-w-lg">
    <form wire:submit="createInvitation" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Invite a workspace member') }}</flux:heading>
            <flux:subheading>{{ __('Send an invitation to join this workspace.') }}</flux:subheading>
        </div>

        <div class="space-y-4">
            <flux:input wire:model="inviteEmail" type="email" :label="__('Email address')" required data-test="invite-email" />

            <x-ui.select
                model="inviteRole"
                :value="$inviteRole"
                :label="__('Role')"
                :options="collect($this->availableRoles)->mapWithKeys(fn ($role) => [$role['value'] => $role['label']])->all()"
                data-test="invite-role"
            />
        </div>

        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
            <flux:modal.close>
                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" type="submit" data-test="invite-submit">{{ __('Send invitation') }}</flux:button>
        </div>
    </form>
</flux:modal>
