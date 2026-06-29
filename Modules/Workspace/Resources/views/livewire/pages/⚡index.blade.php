<?php

use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Employee\Actions\GrantEmployeeSystemAccess;
use Modules\Employee\Models\Employee;
use Modules\Workspace\Actions\CreateWorkspace;
use Modules\Workspace\DTO\UserWorkspace;
use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Workspace;
use Modules\Workspace\Rules\WorkspaceName;

new #[Title('Workspaces')] class extends Component {
    use PasswordValidationRules;

    public string $name = '';

    public ?int $editingWorkspaceId = null;

    public string $editingWorkspaceName = '';

    public string $deletePassword = '';

    public bool $editingWorkspaceCanDelete = false;

    public ?int $selectedWorkspaceId = null;

    public string $memberSearch = '';

    public ?int $removingMemberId = null;

    public string $removingMemberName = '';

    public ?int $account_employee_id = null;

    public string $account_name = '';

    public string $account_email = '';

    public string $account_password = '';

    public string $account_password_confirmation = '';

    public function mount(): void
    {
        $this->selectedWorkspaceId = $this->workspaces()
            ->firstWhere('isCurrent', true)?->id
            ?? $this->workspaces()->first()?->id;
    }

    public function createWorkspace(CreateWorkspace $createWorkspace): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', new WorkspaceName],
        ]);

        $workspace = $createWorkspace->handle(Auth::user(), $validated['name']);

        $this->selectedWorkspaceId = $workspace->id;

        unset($this->workspaces, $this->selectedWorkspace, $this->selectedWorkspaceMembers);

        $this->dispatch('close-modal', name: 'create-workspace');

        $this->reset('name');

        Flux::toast(variant: 'success', text: __('Workspace created.'));
    }

    public function selectWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::query()->findOrFail($workspaceId);

        Gate::authorize('view', $workspace);

        $this->selectedWorkspaceId = $workspace->id;
        $this->memberSearch = '';

        unset($this->selectedWorkspace, $this->selectedWorkspaceMembers, $this->availableEmployees, $this->selectedWorkspaceCanAddMember);
    }

    public function addExistingMember(int $employeeId): void
    {
        $workspace = Workspace::query()->findOrFail($this->selectedWorkspaceId);

        Gate::authorize('addMember', $workspace);

        $employee = Employee::query()
            ->whereNotNull('user_id')
            ->findOrFail($employeeId);

        $user = User::query()->findOrFail($employee->user_id);

        if ($user->belongsToWorkspace($workspace)) {
            $this->addError('memberSearch', __('This user is already a workspace member.'));

            return;
        }

        $workspace->members()->attach($user, [
            'role' => WorkspaceRole::Member->value,
        ]);

        unset($this->selectedWorkspaceMembers, $this->availableEmployees);

        Flux::toast(variant: 'success', text: __('Member added.'));
    }

    public function openCreateEmployeeAccountModal(int $employeeId): void
    {
        $workspace = Workspace::query()->findOrFail($this->selectedWorkspaceId);

        Gate::authorize('addMember', $workspace);

        $employee = Employee::query()->findOrFail($employeeId);

        if ($employee->user_id !== null) {
            Flux::toast(variant: 'warning', text: __('Employee already has an account.'));

            return;
        }

        $this->account_employee_id = $employee->id;
        $this->account_name = $employee->full_name;
        $this->account_email = $employee->email ?? '';
        $this->account_password = '';
        $this->account_password_confirmation = '';

        $this->dispatch('close-modal', name: 'add-workspace-members');
        $this->dispatch('modal-show', name: 'create-employee-account');
    }

    public function createEmployeeAccount(GrantEmployeeSystemAccess $grantEmployeeSystemAccess): void
    {
        $workspace = Workspace::query()->findOrFail($this->selectedWorkspaceId);

        Gate::authorize('addMember', $workspace);

        $validated = $this->validate([
            'account_employee_id' => ['required', 'integer', 'exists:employees,id'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'account_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $employee = Employee::query()->findOrFail($validated['account_employee_id']);

        if ($employee->user_id !== null) {
            Flux::toast(variant: 'warning', text: __('Employee already has an account.'));
            $this->dispatch('close-modal', name: 'create-employee-account');

            return;
        }

        $employee->forceFill([
            'full_name' => $validated['account_name'],
            'email' => $validated['account_email'],
        ])->save();

        $user = $grantEmployeeSystemAccess->handle($employee, password: $validated['account_password']);

        $workspace->members()->syncWithoutDetaching([
            $user->id => [
                'role' => WorkspaceRole::Member->value,
            ],
        ]);

        $user->switchWorkspace($workspace);

        $this->reset(
            'account_employee_id',
            'account_name',
            'account_email',
            'account_password',
            'account_password_confirmation',
        );

        unset($this->selectedWorkspaceMembers, $this->availableEmployees);

        $this->dispatch('close-modal', name: 'create-employee-account');

        Flux::toast(variant: 'success', text: __('Employee account created.'));
    }

    public function confirmRemoveMember(int $userId): void
    {
        $workspace = Workspace::query()->findOrFail($this->selectedWorkspaceId);

        Gate::authorize('removeMember', $workspace);

        $member = $workspace->members()
            ->where('users.id', $userId)
            ->wherePivot('role', '!=', WorkspaceRole::Owner->value)
            ->firstOrFail();

        $this->removingMemberId = $member->id;
        $this->removingMemberName = $member->name;

        $this->dispatch('modal-show', name: 'remove-workspace-member');
    }

    public function removeMember(): void
    {
        $workspace = Workspace::query()->findOrFail($this->selectedWorkspaceId);

        Gate::authorize('removeMember', $workspace);

        $workspace->memberships()
            ->where('user_id', $this->removingMemberId)
            ->where('role', '!=', WorkspaceRole::Owner->value)
            ->firstOrFail()
            ->delete();

        $this->reset('removingMemberId', 'removingMemberName');

        unset($this->selectedWorkspaceMembers, $this->availableEmployees);

        $this->dispatch('close-modal', name: 'remove-workspace-member');

        Flux::toast(variant: 'success', text: __('Member removed.'));
    }

    public function editWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::query()->findOrFail($workspaceId);

        Gate::authorize('update', $workspace);

        $this->editingWorkspaceId = $workspace->id;
        $this->editingWorkspaceName = $workspace->name;
        $this->editingWorkspaceCanDelete = Gate::allows('delete', $workspace);

        $this->dispatch('modal-show', name: 'edit-workspace');
    }

    public function updateWorkspace(): void
    {
        $workspace = Workspace::query()->findOrFail($this->editingWorkspaceId);

        Gate::authorize('update', $workspace);

        $validated = $this->validate([
            'editingWorkspaceName' => ['required', 'string', 'max:255', new WorkspaceName],
        ]);

        $workspace->update(['name' => $validated['editingWorkspaceName']]);

        unset($this->workspaces, $this->selectedWorkspace, $this->selectedWorkspaceMembers);

        $this->dispatch('close-modal', name: 'edit-workspace');

        Flux::toast(variant: 'success', text: __('Workspace updated.'));
    }

    public function deleteWorkspace(): void
    {
        $workspace = Workspace::query()->findOrFail($this->editingWorkspaceId);

        Gate::authorize('delete', $workspace);

        $this->validate([
            'deletePassword' => $this->currentPasswordRules(),
        ]);

        $user = Auth::user();
        $fallbackWorkspace = $user->isCurrentWorkspace($workspace)
            ? $user->fallbackWorkspace($workspace)
            : null;

        DB::transaction(function () use ($workspace, $user): void {
            User::where('current_workspace_id', $workspace->id)
                ->where('id', '!=', $user->id)
                ->each(fn (User $affectedUser) => $affectedUser->switchWorkspace($affectedUser->personalWorkspace()));

            $workspace->invitations()->delete();
            $workspace->memberships()->delete();
            $workspace->delete();
        });

        if ($fallbackWorkspace) {
            $user->switchWorkspace($fallbackWorkspace);
        } elseif ($user->isCurrentWorkspace($workspace)) {
            $user->forceFill(['current_workspace_id' => null])->save();
        }

        $this->dispatch('close-modal', name: 'edit-workspace');
        $this->dispatch('close-modal', name: 'confirm-workspace-deletion');

        Flux::toast(variant: 'success', text: __('Workspace deleted.'));

        $this->reset('deletePassword');

        $this->redirectRoute('workspaces.index', navigate: true);
    }

    public function leaveWorkspace(int $workspaceId): void
    {
        $workspace = Workspace::findOrFail($workspaceId);
        $user = Auth::user();

        Gate::authorize('leave', $workspace);

        $fallbackWorkspace = $user->isCurrentWorkspace($workspace)
            ? $user->fallbackWorkspace($workspace)
            : null;

        $workspace->memberships()
            ->where('user_id', $user->id)
            ->delete();

        if ($fallbackWorkspace) {
            $user->switchWorkspace($fallbackWorkspace);
        }

        $this->dispatch('close-modal', name: "leave-workspace-{$workspaceId}");

        Flux::toast(variant: 'success', text: __('You left the workspace ":name"', ['name' => $workspace->name]));

        $this->redirectRoute('workspaces.index', navigate: true);
    }

    /**
     * @return Collection<int, UserWorkspace>
     */
    #[Computed]
    public function workspaces(): Collection
    {
        return Auth::user()->toUserWorkspaces(includeCurrent: true);
    }

    #[Computed]
    public function selectedWorkspace(): ?Workspace
    {
        if (! $this->selectedWorkspaceId) {
            return null;
        }

        return Workspace::query()->find($this->selectedWorkspaceId);
    }

    #[Computed]
    public function selectedWorkspaceMembers(): array
    {
        $workspace = $this->selectedWorkspace;

        if (! $workspace || Gate::denies('view', $workspace)) {
            return [];
        }

        return $workspace->members()
            ->with(['employee.staffPosition'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role->value,
                'position' => $member->employee?->staffPosition?->name ?: ($member->employee?->position ?? null),
            ])
            ->all();
    }

    #[Computed]
    public function selectedWorkspaceCanAddMember(): bool
    {
        $workspace = $this->selectedWorkspace;

        return $workspace && Gate::allows('addMember', $workspace);
    }

    #[Computed]
    public function selectedWorkspaceCanRemoveMember(): bool
    {
        $workspace = $this->selectedWorkspace;

        return $workspace && Gate::allows('removeMember', $workspace);
    }

    #[Computed]
    public function availableEmployees(): array
    {
        $workspace = $this->selectedWorkspace;

        if (! $workspace || Gate::denies('addMember', $workspace)) {
            return [];
        }

        $search = trim($this->memberSearch);

        return Employee::query()
            ->with(['staffPosition', 'user'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhereHas('staffPosition', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('full_name')
            ->limit(50)
            ->get(['id', 'user_id', 'full_name', 'email', 'position', 'staff_position_id'])
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->full_name,
                'email' => $employee->email ?: $employee->user?->email,
                'position' => $employee->staffPosition?->name ?: $employee->position,
                'can_add' => $employee->user_id !== null && ! $employee->user?->belongsToWorkspace($workspace),
                'is_member' => $employee->user_id !== null && $employee->user?->belongsToWorkspace($workspace),
                'has_access' => $employee->user_id !== null,
            ])
            ->all();
    }
}; ?>

<section class="w-full h-full min-h-0">
    <x-pages::settings.layout :full-width="true" :content-scroll="false">
        <div class="grid h-full min-h-0 w-full gap-4 xl:grid-cols-[320px_minmax(0,1fr)]">
            <aside class="flex h-full min-h-0 min-w-0 flex-col overflow-hidden rounded-md border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <flux:modal.trigger name="create-workspace">
                        <button type="button"
                            class="inline-flex cursor-pointer items-center gap-1.5 text-sm text-[#006de5] hover:underline dark:text-[#8dc5ff]"
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'create-workspace')"
                            data-test="workspaces-new-workspace-button">
                            <flux:icon name="plus-circle" class="size-4" />
                            <span>{{ __('Create workspace') }}</span>
                        </button>
                    </flux:modal.trigger>

                    <flux:badge>{{ $this->workspaces->count() }}</flux:badge>
                </div>

                <div class="min-h-0 flex-1 space-y-1 overflow-y-auto p-3">
                    @forelse ($this->workspaces as $workspace)
                        @php($isSelected = $selectedWorkspaceId === $workspace->id)

                        <div
                            wire:key="workspace-{{ $workspace->id }}"
                            class="group grid min-h-12 grid-cols-[minmax(0,1fr)_auto] items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors {{ $isSelected ? 'bg-zinc-50 dark:bg-zinc-900' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900' }}"
                            data-test="workspace-row">
                            <button type="button" wire:click="selectWorkspace({{ $workspace->id }})" class="min-w-0 text-left">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="truncate font-medium text-zinc-900 transition-colors group-hover:text-[#006de5] dark:text-zinc-100">{{ $workspace->name }}</span>
                                    @if ($workspace->isPersonal)
                                        <flux:badge color="zinc">{{ __('Personal') }}</flux:badge>
                                    @endif
                                </span>
                                <span class="block truncate text-xs text-zinc-500 transition-colors group-hover:text-zinc-700 dark:group-hover:text-zinc-300">
                                    {{ $workspace->isCurrent ? __('Current workspace') : $workspace->slug }}
                                </span>
                            </button>

                            <div class="flex items-center gap-1">
                                @if (! $workspace->isPersonal && $workspace->role !== 'owner')
                                    <flux:modal.trigger :name="'leave-workspace-'.$workspace->id">
                                        <button type="button"
                                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                                            x-data=""
                                            x-on:click.prevent="$dispatch('open-modal', 'leave-workspace-{{ $workspace->id }}')"
                                            data-test="workspace-leave-button"
                                            title="{{ __('Leave workspace') }}">
                                            <flux:icon name="arrow-right-start-on-rectangle" class="size-3.5" />
                                        </button>
                                    </flux:modal.trigger>
                                @endif

                                @if ($workspace->role !== 'member')
                                    <flux:modal.trigger name="edit-workspace">
                                        <button type="button"
                                            wire:click="editWorkspace({{ $workspace->id }})"
                                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                                            data-test="workspace-edit-button"
                                            title="{{ __('Edit workspace') }}">
                                            <flux:icon name="pencil" class="size-3.5" />
                                        </button>
                                    </flux:modal.trigger>
                                @endif
                            </div>
                        </div>

                        @if (! $workspace->isPersonal && $workspace->role !== 'owner')
                            <flux:modal :name="'leave-workspace-'.$workspace->id" focusable class="max-w-lg p-4!">
                                <form wire:submit="leaveWorkspace({{ $workspace->id }})" class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('Leave workspace') }}</flux:heading>
                                        <flux:subheading>
                                            {{ __('Are you sure you want to leave :name?', ['name' => $workspace->name]) }}
                                        </flux:subheading>
                                    </div>

                                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                                        <flux:modal.close>
                                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>

                                        <flux:button variant="danger" type="submit" data-test="leave-workspace-confirm">
                                            {{ __('Leave workspace') }}
                                        </flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        @endif
                    @empty
                        <div class="px-3 py-8 text-center text-sm text-zinc-500">
                            {{ __('You don\'t belong to any workspaces yet.') }}
                        </div>
                    @endforelse
                </div>
            </aside>

            <section class="flex h-full min-h-0 min-w-0 flex-col overflow-hidden rounded-md border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
                    <div class="min-w-0">
                        <flux:heading size="lg">{{ __('Workspace members') }}</flux:heading>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Users with access to this company') }}
                        </flux:text>
                    </div>

                    <div class="flex shrink-0 items-center gap-3">
                        @if ($this->selectedWorkspaceCanAddMember)
                            <flux:modal.trigger name="add-workspace-members">
                                <button type="button"
                                    class="inline-flex cursor-pointer items-center gap-1.5 text-sm text-[#006de5] hover:underline dark:text-[#8dc5ff]">
                                    <flux:icon name="plus-circle" class="size-4" />
                                    <span>{{ __('Add member') }}</span>
                                </button>
                            </flux:modal.trigger>
                        @endif

                        <flux:badge>{{ count($this->selectedWorkspaceMembers) }}</flux:badge>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    @if ($selectedWorkspace = $this->selectedWorkspace)
                        <div class="overflow-hidden rounded-md border border-zinc-200 dark:border-zinc-800">
                            <div class="grid min-w-[520px] grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_6rem] gap-3 border-b border-zinc-200 px-4 py-2 text-xs font-medium uppercase tracking-wide text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                                <span>{{ __('Full name') }}</span>
                                <span>{{ __('Position') }}</span>
                                <span class="text-right">{{ __('Actions') }}</span>
                            </div>

                            @forelse ($this->selectedWorkspaceMembers as $member)
                                <div
                                    wire:key="selected-workspace-member-{{ $member['id'] }}"
                                    class="grid min-w-[520px] grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_6rem] items-center gap-3 border-b border-zinc-100 px-4 py-2.5 text-sm last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-black"
                                    data-test="workspace-member-row">
                                    <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $member['name'] }}
                                    </div>

                                    <div class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $member['position'] ?: __('Not set') }}
                                    </div>

                                    <div class="flex justify-end">
                                        @if ($this->selectedWorkspaceCanRemoveMember && $member['role'] !== 'owner')
                                            <button type="button"
                                                wire:click="confirmRemoveMember({{ $member['id'] }})"
                                                class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950 dark:hover:text-red-300"
                                                title="{{ __('Delete') }}">
                                                <flux:icon name="trash" class="size-3.5" />
                                            </button>
                                        @else
                                            <span class="text-sm text-zinc-400">—</span>
                                        @endif
                                    </div>
                                </div>
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
                    @else
                        <div class="flex min-h-56 items-center justify-center rounded-md border border-dashed border-zinc-200 px-6 text-center dark:border-zinc-800">
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Select a workspace to view members') }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </x-pages::settings.layout>

    <flux:modal name="create-workspace" :show="$errors->has('name')" focusable class="max-w-lg p-4!">
        <form wire:submit="createWorkspace" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create workspace') }}</flux:heading>
            </div>

            <flux:input wire:model="name" :label="__('Workspace name')" type="text" required autofocus data-test="create-workspace-name" />

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button size="sm" variant="primary" type="submit" data-test="create-workspace-submit">
                    {{ __('Create workspace') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-workspace" :show="$errors->has('editingWorkspaceName')" focusable class="max-w-lg p-4!">
        <form wire:submit="updateWorkspace" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Edit workspace') }}</flux:heading>
                <flux:subheading>{{ __('Change the workspace name and keep its profile aligned with the current team.') }}</flux:subheading>
            </div>

            <flux:input wire:model="editingWorkspaceName" :label="__('Workspace name')" type="text" required data-test="edit-workspace-name" />

            @if ($editingWorkspaceCanDelete)
                <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ __('Deleting a workspace is permanent. All workspace invitations, memberships, and related workspace access will be removed.') }}
                </div>
            @endif

            <div class="flex items-center justify-between gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                @if ($editingWorkspaceCanDelete)
                    <flux:modal.trigger name="confirm-workspace-deletion">
                        <flux:button type="button" size="sm" variant="danger" data-test="delete-workspace-button">
                            {{ __('Delete workspace') }}
                        </flux:button>
                    </flux:modal.trigger>
                @else
                    <span></span>
                @endif

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>

                    <flux:button size="sm" variant="primary" type="submit" data-test="update-workspace-submit">
                        {{ __('Save changes') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="add-workspace-members" :show="$errors->has('memberSearch')" focusable class="max-w-2xl p-4!">
        <div class="space-y-5">
            <div>
                <flux:heading size="lg">{{ __('Add member') }}</flux:heading>
                <flux:subheading>{{ __('Select an existing user and assign a workspace role.') }}</flux:subheading>
            </div>

            <flux:input
                wire:model.live.debounce.250ms="memberSearch"
                :label="false"
                type="search"
                :placeholder="__('Search by name, position, department')"
                autofocus
            />

            <flux:error name="memberSearch" />

            <div class="max-h-[420px] overflow-y-auto rounded-md border border-zinc-200 dark:border-zinc-800">
                @forelse ($this->availableEmployees as $employee)
                    <div
                        wire:key="available-workspace-employee-{{ $employee['id'] }}"
                        class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3 border-b border-zinc-100 px-4 py-2.5 last:border-b-0 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-black">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $employee['name'] }}</div>
                            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $employee['position'] ?: ($employee['email'] ?: __('No position assigned')) }}
                            </div>
                        </div>

                        @if ($employee['can_add'])
                            <button type="button"
                                wire:click="addExistingMember({{ $employee['id'] }})"
                                class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-full bg-[#006de5] text-white transition-colors hover:bg-[#005bc0] dark:bg-[#2f8cff] dark:hover:bg-[#1f7be8]"
                                title="{{ __('Add') }}">
                                <flux:icon name="plus" class="size-3.5 text-white!" />
                            </button>
                        @elseif ($employee['is_member'])
                            <flux:badge color="zinc">{{ __('Already added') }}</flux:badge>
                        @else
                            <button type="button"
                                wire:click="openCreateEmployeeAccountModal({{ $employee['id'] }})"
                                class="text-xs text-[#006de5] hover:underline dark:text-[#8dc5ff] cursor-pointer">
                                {{ __('Create account') }}
                            </button>
                        @endif
                    </div>
                @empty
                    <div class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No workers yet') }}
                    </div>
                @endforelse
            </div>

            <div class="flex justify-end border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="create-employee-account" :show="$errors->has('account_employee_id') || $errors->has('account_name') || $errors->has('account_email') || $errors->has('account_password')" focusable class="max-w-lg p-4!">
        <form wire:submit="createEmployeeAccount" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Create account') }}</flux:heading>
                <flux:subheading>{{ __('Authorization credentials for employee login') }}</flux:subheading>
            </div>

            <div class="grid gap-4">
                <flux:input wire:model="account_name" :label="__('Name')" type="text" placeholder="{{ __('Enter full name') }}" />

                <flux:input wire:model="account_email" :label="__('Email')" type="email" placeholder="{{ __('Enter email address') }}" />

                <flux:input wire:model="account_password" :label="__('Password')" type="password" placeholder="{{ __('Enter password') }}" viewable />

                <flux:input wire:model="account_password_confirmation" :label="__('Confirm password')" type="password" placeholder="{{ __('Confirm password') }}" viewable />
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" size="sm" variant="primary" wire:loading.attr="disabled" wire:target="createEmployeeAccount">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="remove-workspace-member" focusable class="max-w-lg p-4!">
        <form wire:submit="removeMember" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Remove member') }}</flux:heading>
                <flux:subheading>
                    {{ __('Are you sure you want to remove :name from this workspace?', ['name' => $removingMemberName]) }}
                </flux:subheading>
            </div>

            <div class="rounded-md border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                {{ __('The user will lose access to this workspace and related workspace data.') }}
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" size="sm" variant="danger" data-test="confirm-remove-workspace-member-button">
                    {{ __('Delete') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-workspace-deletion" :show="$errors->has('deletePassword')" focusable class="max-w-lg p-4!">
        <form wire:submit="deleteWorkspace" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure?') }}</flux:heading>
                <flux:subheading>
                    {{ __('Once this workspace is deleted, its resources and access data will be permanently removed. Please enter your password to confirm deletion.') }}
                </flux:subheading>
            </div>

            <flux:input wire:model="deletePassword" :label="__('Password')" type="password" viewable />

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <flux:modal.close>
                    <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" size="sm" variant="danger" data-test="confirm-delete-workspace-button">
                    {{ __('Delete workspace') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
