<?php

use App\Data\WorkspacePermissions;
use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\User;
use App\Notifications\Workspaces\WorkspaceInvitation as WorkspaceInvitationNotification;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Workspace\Rules\UniqueWorkspaceInvitation;
use Modules\Workspace\Rules\WorkspaceName;

new class extends Component
{
    public Workspace $workspaceModel;

    public string $workspaceName = '';

    public array $workspaceData = [];

    public array $members = [];

    public array $invitations = [];

    public array $availableRoles = [];

    public array $availableSystemUsers = [];

    public ?string $existingUserId = null;

    public string $existingUserRole = 'member';

    public string $inviteEmail = '';

    public string $inviteRole = 'member';

    public string $memberSearch = '';

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

    public function addExistingMember(): void
    {
        Gate::authorize('addMember', $this->workspaceModel);

        $validated = Validator::make([
            'existingUserId' => $this->existingUserId,
            'existingUserRole' => $this->existingUserRole,
        ], [
            'existingUserId' => ['required', 'integer', 'exists:users,id'],
            'existingUserRole' => ['required', 'string', Rule::in(collect(WorkspaceRole::assignable())->pluck('value')->all())],
        ])->validate();

        $user = User::query()->findOrFail((int) $validated['existingUserId']);

        if ($user->belongsToWorkspace($this->workspaceModel)) {
            $this->addError('existingUserId', __('This user is already a workspace member.'));

            return;
        }

        $this->workspaceModel->members()->attach($user, [
            'role' => WorkspaceRole::from($validated['existingUserRole'])->value,
        ]);

        $this->existingUserId = null;
        $this->existingUserRole = WorkspaceRole::Member->value;

        $this->populateWorkspaceData();

        Flux::toast(variant: 'success', text: __('Member added.'));
    }

    public function createInvitation(): void
    {
        Gate::authorize('inviteMember', $this->workspaceModel);

        $validated = Validator::make([
            'inviteEmail' => $this->inviteEmail,
            'inviteRole' => $this->inviteRole,
        ], [
            'inviteEmail' => ['required', 'string', 'email', 'max:255', new UniqueWorkspaceInvitation($this->workspaceModel)],
            'inviteRole' => ['required', 'string', Rule::enum(WorkspaceRole::class)],
        ])->validate();

        $invitation = $this->workspaceModel->invitations()->create([
            'email' => $validated['inviteEmail'],
            'role' => WorkspaceRole::from($validated['inviteRole']),
            'invited_by' => Auth::id(),
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new WorkspaceInvitationNotification($invitation));

        $this->inviteEmail = '';
        $this->inviteRole = WorkspaceRole::Member->value;

        $this->populateWorkspaceData();

        Flux::toast(variant: 'success', text: __('Invitation sent.'));
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

        $this->members = $workspace->members()
            ->with(['employee.staffPosition', 'employee.departments'])
            ->get()
            ->map(fn (User $member) => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'avatar' => $member->avatar ?? null,
            'role' => $member->pivot->role->value,
            'role_label' => $member->pivot->role->label(),
            'position' => $member->employee?->staffPosition?->name ?: ($member->employee?->position ?? null),
            'department' => $member->employee?->departments->pluck('name')->join(', ') ?: null,
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

        $this->availableSystemUsers = User::query()
            ->whereDoesntHave('workspaceMemberships', fn ($query) => $query->where('workspace_id', $workspace->id))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn (User $user) => [$user->id => "{$user->name} · {$user->email}"])
            ->all();

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

    #[Computed]
    public function filteredMembers(): array
    {
        $search = Str::of($this->memberSearch)->trim()->lower()->toString();

        if ($search === '') {
            return $this->members;
        }

        return collect($this->members)
            ->filter(function (array $member) use ($search): bool {
                $haystack = Str::of(trim(sprintf(
                    '%s %s %s %s',
                    $member['name'] ?? '',
                    $member['email'] ?? '',
                    $member['position'] ?? '',
                    $member['department'] ?? '',
                )))->lower()->toString();

                return str_contains($haystack, $search);
            })
            ->values()
            ->all();
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Workspaces') }}</flux:heading>

    <x-pages::settings.layout
        :heading="__('Workspace settings')"
        :subheading="__('Manage company profile, users and access rights')"
        :full-width="true"
    >
        <form wire:submit="updateWorkspace" class="mt-5 grid gap-4 xl:h-[calc(100vh-13rem)] xl:grid-cols-[360px_minmax(0,1fr)]">
            <section class="flex min-h-[560px] flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 xl:h-full">
                <div class="flex items-start gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[color-mix(in_oklab,var(--color-brand-primary)_12%,white)] dark:bg-[color-mix(in_oklab,var(--color-brand-primary)_18%,black)]">
                        <flux:icon name="building-office-2" class="size-5" />
                    </div>

                    <div class="min-w-0">
                        <flux:heading class="text-base">{{ __('Workspace details') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Company name and technical identifier') }}
                        </flux:text>
                    </div>
                </div>

                <div class="flex flex-1 flex-col gap-4 overflow-y-auto px-4 py-4">
                    @if ($this->permissions->canUpdateWorkspace)
                        <div class="space-y-4">
                            <flux:input
                                wire:model="workspaceName"
                                :label="__('Workspace name')"
                                required
                                data-test="workspace-name-input"
                            />

                            <div class="flex items-center gap-2">
                                <flux:button variant="primary" type="submit" icon="check" data-test="workspace-save-button">
                                    {{ __('Save changes') }}
                                </flux:button>
                            </div>
                        </div>
                    @else
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-black">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Workspace name') }}
                            </flux:text>
                            <p class="mt-1 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $workspaceData['name'] }}</p>
                        </div>
                    @endif

                </div>
            </section>

            <section class="flex min-h-[560px] flex-col overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950 xl:h-full">
                <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <div class="flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-[color-mix(in_oklab,var(--color-brand-primary)_12%,white)] dark:bg-[color-mix(in_oklab,var(--color-brand-primary)_18%,black)]">
                            <flux:icon name="users" class="size-5" />
                        </div>

                        <div class="min-w-0">
                            <flux:heading class="text-base">{{ __('Workspace members') }}</flux:heading>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Users with access to this company') }}
                            </flux:text>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-3 pt-1">
                        @if ($this->permissions->canAddMember || $this->permissions->canCreateInvitation)
                            <flux:modal.trigger name="workspace-members-modal">
                                <button type="button" class="cursor-pointer text-sm font-medium text-[#013763] hover:underline dark:text-[#8dc5ff]">
                                    {{ __('Invite') }}
                                </button>
                            </flux:modal.trigger>
                        @endif
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="min-w-[640px]">
                        <div class="grid grid-cols-[minmax(0,1fr)_12rem_2.5rem] items-center gap-3 border-b border-zinc-200 px-4 py-2 text-xs font-medium uppercase tracking-wide text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                            <span>{{ __('User') }}</span>
                            <span>{{ __('Role') }}</span>
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </div>

                        @forelse ($members as $member)
                            <div class="grid grid-cols-[minmax(0,1fr)_12rem_2.5rem] items-center gap-3 border-b border-zinc-100 px-4 py-2.5 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-black" data-test="member-row">
                                <div class="flex min-w-0 items-center gap-3">
                                    <flux:avatar size="sm" :name="$member['name']" :initials="strtoupper(substr($member['name'], 0, 1))" />
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $member['name'] }}</div>
                                        <flux:text class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $member['email'] }}</flux:text>
                                    </div>
                                </div>

                                @if ($member['role'] !== 'owner' && $this->permissions->canUpdateMember)
                                    <flux:dropdown position="bottom" align="end">
                                        <button type="button" class="inline-flex w-full cursor-pointer items-center justify-between gap-2 rounded px-2 py-1 text-left text-sm font-medium text-[#013763] hover:bg-zinc-100 dark:text-[#8dc5ff] dark:hover:bg-zinc-900" data-test="member-role-trigger">
                                            <span class="truncate">{{ $member['role_label'] }}</span>
                                            <flux:icon.chevron-down class="size-3.5 shrink-0" />
                                        </button>
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
                                    <span class="truncate px-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $member['role_label'] }}</span>
                                @endif

                                <div class="flex justify-end">
                                    @if ($member['role'] !== 'owner' && $this->permissions->canRemoveMember)
                                        <flux:modal.trigger name="remove-member-{{ $member['id'] }}">
                                            <flux:tooltip :content="__('Remove member')">
                                                <flux:button
                                                    variant="ghost"
                                                    size="sm"
                                                    icon="trash"
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
                        @empty
                            <div class="flex min-h-56 items-center justify-center px-6 text-center">
                                <div class="max-w-sm">
                                    <flux:icon name="users" class="mx-auto size-6 text-zinc-400" />
                                    <flux:text class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ __('No members yet') }}
                                    </flux:text>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if (count($invitations) > 0)
                        <div class="mt-4 space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                            <div class="flex items-center gap-3">
                                <flux:icon name="envelope" class="size-5 text-zinc-500" />
                                <div>
                                    <flux:heading class="text-base">{{ __('Pending invitations') }}</flux:heading>
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Invitations that have not been accepted yet') }}</flux:text>
                                </div>
                            </div>

                            <div class="space-y-3">
                                @foreach ($invitations as $invitation)
                                    <div class="flex items-center justify-between gap-4 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-black" data-test="invitation-row">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex size-10 items-center justify-center rounded-lg bg-[color-mix(in_oklab,var(--color-brand-primary)_12%,white)] dark:bg-[color-mix(in_oklab,var(--color-brand-primary)_18%,black)]">
                                                <flux:icon name="envelope" class="size-5" />
                                            </div>
                                            <div class="min-w-0">
                                                <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $invitation['email'] }}</div>
                                                <flux:text class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $invitation['role_label'] }}</flux:text>
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
                </div>
            </section>
        </form>

        @if ($this->permissions->canDeleteWorkspace && ! $workspaceData['is_personal'])
            <section class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-4 text-red-700 dark:border-red-200/10 dark:bg-red-900/20 dark:text-red-100">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-red-500/10">
                        <flux:icon name="trash" class="size-5 text-red-600" />
                    </div>

                    <div class="flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <flux:heading class="text-base">{{ __('Delete workspace') }}</flux:heading>
                                <flux:text class="text-sm text-red-700/80 dark:text-red-100/80">{{ __('Permanently delete your workspace') }}</flux:text>
                            </div>

                            <flux:modal.trigger name="delete-workspace">
                                <flux:button variant="danger" icon="trash" data-test="delete-workspace-button">
                                    {{ __('Delete workspace') }}
                                </flux:button>
                            </flux:modal.trigger>
                        </div>

                        <p class="mt-3 text-sm">
                            {{ __('Please proceed with caution, this cannot be undone.') }}
                        </p>
                    </div>
                </div>
            </section>
        @endif
    </x-pages::settings.layout>

    @if ($this->permissions->canCreateInvitation || $this->permissions->canAddMember)
        <flux:modal name="workspace-members-modal" :show="$errors->has('inviteEmail') || $errors->has('inviteRole') || $errors->has('existingUserId') || $errors->has('existingUserRole')" focusable class="max-w-6xl">
            <div class="space-y-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <flux:heading size="lg">{{ __('Workspace participants') }} «{{ $workspaceData['name'] }}»</flux:heading>
                        <flux:subheading>{{ __('Assign workers and control access inside this workspace.') }}</flux:subheading>
                    </div>

                    <flux:modal.close>
                        <button type="button" class="inline-flex size-8 items-center justify-center rounded border border-zinc-200 text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" data-test="workspace-members-modal-close">
                            <flux:icon name="x-mark" class="size-4" />
                        </button>
                    </flux:modal.close>
                </div>

                <div class="space-y-4">
                    @if ($this->permissions->canCreateInvitation)
                        <div class="flex items-center justify-between gap-3">
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Invite employees to this workspace') }}
                            </div>
                            <a class="inline-flex items-center gap-1.5 text-sm font-medium text-[#013763] hover:underline dark:text-[#8dc5ff]" href="#">
                                <flux:icon name="chat-bubble-left-right" class="size-4" />
                                {{ __('Group chat') }} «{{ $workspaceData['name'] }}»
                            </a>
                        </div>

                        <form wire:submit="createInvitation" class="flex flex-col gap-2 sm:flex-row sm:items-end">
                            <flux:input
                                wire:model="inviteEmail"
                                type="email"
                                :label="false"
                                :placeholder="__('Enter email address')"
                                required
                            />

                            <flux:button type="submit" variant="primary" icon="paper-airplane" class="w-full sm:w-auto" data-test="invite-member-submit">
                                {{ __('Invite') }}
                            </flux:button>
                        </form>
                        <flux:error name="inviteEmail" />
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Project role management for this workspace is configured in the roles section.') }}</flux:text>
                        <a href="{{ route('roles.index') }}" class="text-sm font-medium text-[#013763] hover:underline dark:text-[#8dc5ff]">
                            {{ __('Role settings') }}
                        </a>
                    </div>
                </div>

                @if ($this->permissions->canAddMember)
                    <div class="space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                        <flux:heading size="sm">{{ __('Add from system') }}</flux:heading>

                        <form wire:submit="addExistingMember" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_12rem_9rem]">
                            <x-ui.select
                                model="existingUserId"
                                :value="$existingUserId"
                                :label="__('User')"
                                :options="$availableSystemUsers"
                                :placeholder="__('Select user')"
                                :disabled="count($availableSystemUsers) === 0"
                                required
                            />

                            <x-ui.select
                                model="existingUserRole"
                                :value="$existingUserRole"
                                :label="__('Role')"
                                :options="collect($availableRoles)->mapWithKeys(fn ($role) => [$role['value'] => $role['label']])->all()"
                                required
                            />

                            <flux:button type="submit" size="sm" variant="primary" :disabled="count($availableSystemUsers) === 0" class="w-full lg:w-auto">
                                {{ __('Add member') }}
                            </flux:button>
                        </form>

                        @if (count($availableSystemUsers) === 0)
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No available system users') }}</flux:text>
                        @endif
                    </div>
                @endif

                <div class="space-y-2">
                    <flux:input
                        wire:model.live.debounce.250ms="memberSearch"
                        type="text"
                        :label="false"
                        :placeholder="__('Search by name, position, department')"
                    />

                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-800">
                        <div class="grid min-w-[640px] grid-cols-[2rem_minmax(0,1.7fr)_minmax(0,1fr)_minmax(0,1fr)_12rem_2.5rem] items-center gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-2 text-xs font-medium uppercase tracking-wide text-zinc-400 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-500">
                            <span class="sr-only">Выбрать</span>
                            <span>{{ __('User') }}</span>
                            <span>{{ __('Position') }}</span>
                            <span>{{ __('Department') }}</span>
                            <span>{{ __('Role in workspace') }}</span>
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </div>

                        @forelse ($this->filteredMembers as $member)
                            <div class="grid min-w-[640px] grid-cols-[2rem_minmax(0,1.7fr)_minmax(0,1fr)_minmax(0,1fr)_12rem_2.5rem] items-center gap-3 border-b border-zinc-100 px-4 py-2.5 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-black">
                                <label class="inline-flex size-4 items-center justify-center">
                                    <input type="checkbox" checked disabled class="size-4 rounded border-zinc-300 bg-zinc-100 text-[#013763] dark:border-zinc-700 dark:bg-zinc-900 dark:text-[#8dc5ff]" />
                                </label>

                                <div class="min-w-0">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <flux:avatar size="sm" :name="$member['name']" :initials="strtoupper(substr($member['name'], 0, 1))" />
                                        <div class="min-w-0">
                                            <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $member['name'] }}</div>
                                            <flux:text class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $member['email'] }}</flux:text>
                                        </div>
                                    </div>
                                </div>

                                <div class="truncate text-sm text-zinc-700 dark:text-zinc-200">{{ $member['position'] ?: '—' }}</div>
                                <div class="truncate text-sm text-zinc-700 dark:text-zinc-200">{{ $member['department'] ?: '—' }}</div>

                                @if ($member['role'] !== 'owner' && $this->permissions->canUpdateMember)
                                    <flux:dropdown position="bottom" align="end">
                                        <button type="button" class="inline-flex w-full cursor-pointer items-center justify-between gap-2 rounded px-2 py-1 text-left text-sm font-medium text-[#013763] hover:bg-zinc-100 dark:text-[#8dc5ff] dark:hover:bg-zinc-900" data-test="member-role-trigger">
                                            <span class="truncate">{{ $member['role_label'] }}</span>
                                            <flux:icon.chevron-down class="size-3.5 shrink-0" />
                                        </button>
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
                                    <span class="truncate px-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $member['role_label'] }}</span>
                                @endif

                                <span></span>
                            </div>
                        @empty
                            <div class="px-4 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No members yet') }}
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="button" variant="primary" disabled>{{ __('Save') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif

    @if ($this->permissions->canDeleteWorkspace && ! $workspaceData['is_personal'])
        <livewire:pages::workspaces.delete-workspace-modal :workspace="$workspaceModel" />
    @endif
</section>
