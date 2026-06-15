<?php

use App\Data\WorkspacePermissions;
use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Workspace\Rules\WorkspaceName;

new class extends Component
{
    public Workspace $workspaceModel;

    public string $workspaceName = '';

    public array $workspaceData = [];

    public array $members = [];

    public array $invitations = [];

    public array $availableRoles = [];

    public bool $isCurrentWorkspace = false;

    public function mount(Workspace $workspace): void
    {
        $this->workspaceModel = $workspace;
        $this->workspaceName = $workspace->name;

        $this->populateWorkspaceData();
    }

    public function updateWorkspace(): void
    {
        Gate::authorize('update', $this->workspaceModel);

        $validated = $this->validate([
            'workspaceName' => ['required', 'string', 'max:255', new WorkspaceName],
        ]);

        $workspace = DB::transaction(function () use ($validated) {
            $workspace = Workspace::whereKey($this->workspaceModel->id)->lockForUpdate()->firstOrFail();

            $workspace->update(['name' => $validated['workspaceName']]);

            return $workspace;
        });

        $this->workspaceModel = $workspace;

        $this->populateWorkspaceData();

        Flux::toast(variant: 'success', text: __('Workspace updated.'));

        $this->redirectRoute('workspaces.edit', ['workspace' => $this->workspaceModel->fresh()->slug], navigate: true);
    }

    public function updateMember(int $userId, string $role): void
    {
        Gate::authorize('updateMember', $this->workspaceModel);

        $validated = Validator::make(['role' => $role], [
            'role' => ['required', 'string', Rule::enum(WorkspaceRole::class)],
        ])->validate();

        $this->workspaceModel->memberships()
            ->where('user_id', $userId)
            ->firstOrFail()
            ->update(['role' => WorkspaceRole::from($validated['role'])]);

        $this->populateWorkspaceData();

        Flux::toast(variant: 'success', text: __('Member role updated.'));
    }

    private function populateWorkspaceData(): void
    {
        $user = Auth::user();

        $workspace = $this->workspaceModel->fresh();

        $this->workspaceData = [
            'id' => $workspace->id,
            'name' => $workspace->name,
            'slug' => $workspace->slug,
            'is_personal' => $workspace->is_personal,
        ];

        $this->members = $workspace->members()->get()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => $member->avatar ?? null,
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role->label(),
        ])->toArray();

        $this->invitations = $workspace->invitations()
            ->whereNull('accepted_at')
            ->get()
            ->map(fn ($invitation) => [
                'code' => $invitation->code,
                'email' => $invitation->email,
                'role' => $invitation->role->value,
                'role_label' => $invitation->role->label(),
                'created_at' => $invitation->created_at->toISOString(),
            ])->toArray();

        $this->availableRoles = WorkspaceRole::assignable();

        $this->isCurrentWorkspace = $user->isCurrentWorkspace($workspace);
    }

    public function render()
    {
        $workspaceName = $this->workspaceData['name'] ?? $this->workspaceModel->name;

        $title = $this->permissions->canUpdateWorkspace
            ? __('Edit :name', ['name' => $workspaceName])
            : __('View :name', ['name' => $workspaceName]);

        return $this->view()->title($title);
    }

    #[Computed]
    public function permissions(): WorkspacePermissions
    {
        return Auth::user()->toWorkspacePermissions($this->workspaceModel);
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Workspaces') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Workspaces')" :subheading="__('Manage your workspace settings')">
        <div class="space-y-10">
            <div class="space-y-6">
                @if ($this->permissions->canUpdateWorkspace)
                    <div class="space-y-4">
                        <form wire:submit="updateWorkspace" class="space-y-6">
                            <flux:input wire:model="workspaceName" :label="__('Workspace name')" required data-test="workspace-name-input" />

                            <flux:button variant="primary" type="submit" data-test="workspace-save-button">
                                {{ __('Save') }}
                            </flux:button>
                        </form>
                    </div>
                @else
                    <div>
                        <flux:heading>{{ $workspaceData['name'] }}</flux:heading>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:heading>{{ __('Workspace members') }}</flux:heading>
                        @if ($this->permissions->canAddMember || $this->permissions->canUpdateMember || $this->permissions->canRemoveMember)
                            <flux:subheading>{{ __('Manage who belongs to this workspace') }}</flux:subheading>
                        @endif
                    </div>

                    @if ($this->permissions->canCreateInvitation)
                        <flux:modal.trigger name="invite-member">
                            <flux:button variant="primary" icon="user-plus" data-test="invite-member-button">
                                {{ __('Invite member') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>

                <div class="space-y-3">
                    @foreach ($members as $member)
                        <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="member-row">
                            <div class="flex items-center gap-4">
                                <flux:avatar :name="$member['name']" :initials="strtoupper(substr($member['name'], 0, 1))" />
                                <div>
                                    <div class="font-medium">{{ $member['name'] }}</div>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $member['email'] }}</flux:text>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                @if ($member['role'] !== 'owner' && $this->permissions->canUpdateMember)
                                    <flux:dropdown position="bottom" align="end">
                                        <flux:button variant="outline" size="sm" icon:trailing="chevron-down" data-test="member-role-trigger">
                                            {{ $member['role_label'] }}
                                        </flux:button>
                                        <flux:menu>
                                            @foreach ($availableRoles as $role)
                                                <flux:menu.item
                                                    as="button"
                                                    type="button"
                                                    wire:click="updateMember({{ $member['id'] }}, '{{ $role['value'] }}')"
                                                    data-test="member-role-option"
                                                >
                                                    {{ $role['label'] }}
                                                </flux:menu.item>
                                            @endforeach
                                        </flux:menu>
                                    </flux:dropdown>
                                @else
                                    <flux:badge color="zinc">{{ $member['role_label'] }}</flux:badge>
                                @endif

                                @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                                    <flux:modal.trigger name="remove-member-{{ $member['id'] }}">
                                        <flux:tooltip :content="__('Remove member')">
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="x-mark"
                                                data-test="member-remove-button"
                                            />
                                        </flux:tooltip>
                                    </flux:modal.trigger>
                                @endif
                            </div>
                        </div>

                        @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                            <livewire:pages::workspaces.remove-member-modal
                                :workspace="$workspaceModel"
                                :member-id="$member['id']"
                                :member-name="$member['name']"
                                :modal-name="'remove-member-'.$member['id']"
                                :key="'remove-member-modal-'.$member['id']"
                            />
                        @endif
                    @endforeach
                </div>
            </div>

            @if (count($invitations) > 0)
                <div class="space-y-6">
                    <div>
                        <flux:heading>{{ __('Pending invitations') }}</flux:heading>
                        <flux:subheading>{{ __('Invitations that have not been accepted yet') }}</flux:subheading>
                    </div>

                    <div class="space-y-3">
                        @foreach ($invitations as $invitation)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" data-test="invitation-row">
                                <div class="flex items-center gap-4">
                                    <div class="flex size-10 items-center justify-center rounded-full bg-[#013763]/10">
                                        <flux:icon name="envelope" class="text-[#013763]" />
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ $invitation['email'] }}</div>
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ $invitation['role_label'] }}</flux:text>
                                    </div>
                                </div>

                                @if ($this->permissions->canCancelInvitation)
                                    <flux:modal.trigger name="cancel-invitation-{{ $invitation['code'] }}">
                                        <flux:tooltip :content="__('Cancel invitation')">
                                            <flux:button
                                                variant="ghost"
                                                size="sm"
                                                icon="x-mark"
                                                data-test="invitation-cancel-button"
                                            />
                                        </flux:tooltip>
                                    </flux:modal.trigger>
                                @endif
                            </div>
                            @if ($this->permissions->canCancelInvitation)
                                <livewire:pages::workspaces.cancel-invitation-modal
                                    :workspace="$workspaceModel"
                                    :invitation-code="$invitation['code']"
                                    :invitation-email="$invitation['email']"
                                    :modal-name="'cancel-invitation-'.$invitation['code']"
                                    :key="'cancel-invitation-modal-'.$invitation['code']"
                                />
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->permissions->canDeleteWorkspace && ! $workspaceData['is_personal'])
                <div class="space-y-6">
                    <div>
                        <flux:heading>{{ __('Delete workspace') }}</flux:heading>
                        <flux:subheading>{{ __('Permanently delete your workspace') }}</flux:subheading>
                    </div>

                    <div class="space-y-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-200/10 dark:bg-red-900/20 dark:text-red-100">
                        <div>
                            <p class="font-medium">{{ __('Warning') }}</p>
                            <p class="text-sm">{{ __('Please proceed with caution, this cannot be undone.') }}</p>
                        </div>

                        <flux:modal.trigger name="delete-workspace">
                            <flux:button variant="danger" data-test="delete-workspace-button">
                                {{ __('Delete workspace') }}
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                </div>
            @endif
        </div>
    </x-pages::settings.layout>

    @if ($this->permissions->canCreateInvitation)
        <livewire:pages::workspaces.invite-member-modal :workspace="$workspaceModel" />
    @endif

    @if ($this->permissions->canDeleteWorkspace && ! $workspaceData['is_personal'])
        <livewire:pages::workspaces.delete-workspace-modal :workspace="$workspaceModel" />
    @endif
</section>
