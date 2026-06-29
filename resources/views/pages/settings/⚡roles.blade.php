<?php

use App\Models\User;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Modules\Access\Actions\SyncAccessDefaults;
use Modules\Access\Actions\SyncRolePermissions;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Models\Role;
use Modules\Access\Services\PermissionResolver;
use Modules\Access\Support\PermissionCatalog;

new #[Title('Roles & permissions')] class extends Component {
  public ?int $editing_role_id = null;

  public string $role_name = '';

  public string $role_code = '';

  public string $role_scope = 'workspace';

  public string $role_description = '';

  public string $role_form_context = 'create';

  /** @var array<int, string> */
  public array $selected_permission_codes = [];

  public function mount(SyncAccessDefaults $syncAccessDefaults): void
  {
    $syncAccessDefaults->handle();
    $this->authorizeAccess();

    $firstRoleId = $this->manageableRolesQuery()
      ->orderBy('scope')
      ->orderBy('name')
      ->value('id');

    if ($firstRoleId !== null) {
      $this->editRole($firstRoleId);
    }
  }

  public function createNewRole(): void
  {
    $this->authorizeAccess();
    $this->resetForm();
    $this->role_form_context = 'create';
  }

  public function createRole(SyncAccessDefaults $syncAccessDefaults, SyncRolePermissions $syncRolePermissions): void
  {
    $this->role_form_context = 'create';
    $this->saveRole($syncAccessDefaults, $syncRolePermissions);
    $this->dispatch('close-modal', name: 'create-role');
  }

  public function updateRole(SyncAccessDefaults $syncAccessDefaults, SyncRolePermissions $syncRolePermissions): void
  {
    $this->role_form_context = 'edit';
    $this->saveRole($syncAccessDefaults, $syncRolePermissions);
    $this->dispatch('close-modal', name: 'edit-role');
  }

  public function editRole(int $roleId): void
  {
    $this->authorizeAccess();

    $role = $this->manageableRolesQuery()
      ->with('permissions')
      ->findOrFail($roleId);

    $this->editing_role_id = $role->id;
    $this->role_form_context = 'edit';
    $this->role_name = $role->name;
    $this->role_code = $role->code;
    $this->role_scope = $role->scope->value;
    $this->role_description = $role->description ?? '';
    $this->selected_permission_codes = $role->permissions
      ->pluck('code')
      ->sort()
      ->values()
      ->all();
  }

  public function saveRole(SyncAccessDefaults $syncAccessDefaults, SyncRolePermissions $syncRolePermissions): void
  {
    $this->authorizeAccess();
    $syncAccessDefaults->handle();

    $validated = $this->validateRole();
    $scope = RoleScope::from($validated['role_scope']);
    $workspaceId = Auth::user()->current_workspace_id;

    $roleData = [
      'name' => $validated['role_name'],
      'code' => $validated['role_code'],
      'scope' => $scope,
      'workspace_id' => $workspaceId,
      'project_id' => null,
      'description' => $validated['role_description'] ?: null,
      'is_system' => false,
    ];

    $role = $this->editing_role_id
      ? tap($this->manageableRolesQuery()->findOrFail($this->editing_role_id))->update($roleData)
      : Role::query()->create($roleData);

    $syncRolePermissions->handle($role, $validated['selected_permission_codes']);

    $this->editRole($role->id);

    Flux::toast(variant: 'success', text: __('Role saved.'));
  }

  public function savePermissions(SyncRolePermissions $syncRolePermissions): void
  {
    $this->authorizeAccess();

    if (!$this->editing_role_id) {
      throw ValidationException::withMessages([
        'editing_role_id' => __('Select a role first.'),
      ]);
    }

    $validated = $this->validate([
      'selected_permission_codes' => ['array'],
      'selected_permission_codes.*' => ['string', Rule::in(PermissionCatalog::codes())],
    ]);

    $role = $this->manageableRolesQuery()->findOrFail($this->editing_role_id);

    $syncRolePermissions->handle($role, $validated['selected_permission_codes']);

    unset($this->roles);

    Flux::toast(variant: 'success', text: __('Permissions saved.'));
  }

  public function deleteRole(): void
  {
    $this->authorizeAccess();

    if (!$this->editing_role_id) {
      return;
    }

    $role = $this->manageableRolesQuery()->findOrFail($this->editing_role_id);

    if ($role->is_system || $this->roleIsAssigned($role)) {
      throw ValidationException::withMessages([
        'editing_role_id' => __('This role cannot be deleted while it is assigned.'),
      ]);
    }

    $role->delete();
    $this->resetForm();

    $this->dispatch('close-modal', name: 'edit-role');

    Flux::toast(variant: 'success', text: __('Role deleted.'));
  }

  public function updatedRoleName(string $value): void
  {
    if ($this->editing_role_id || $this->role_code !== '') {
      return;
    }

    $this->role_code = Str::slug($value);
  }

  #[Computed]
  public function roles(): Collection
  {
    return $this->manageableRolesQuery()
      ->withCount('permissions')
      ->orderBy('scope')
      ->orderBy('name')
      ->get();
  }

  #[Computed]
  public function permissionGroups(): array
  {
    return PermissionCatalog::grouped();
  }

  private function authorizeAccess(): void
  {
    /** @var User $user */
    $user = Auth::user();
    $permissionResolver = app(PermissionResolver::class);

    if ($permissionResolver->userCan($user, 'roles.manage')) {
      return;
    }

    abort_unless($user->currentWorkspace, 403);
    abort_unless($permissionResolver->userCanInWorkspace($user, $user->currentWorkspace, 'roles.manage'), 403);
  }

  private function manageableRolesQuery(): Builder
  {
    $workspaceId = Auth::user()->current_workspace_id;

    return Role::query()
      ->whereIn('scope', [RoleScope::Workspace->value, RoleScope::Project->value])
      ->where('workspace_id', $workspaceId)
      ->whereNull('project_id');
  }

  private function resetForm(): void
  {
    $this->reset([
      'editing_role_id',
      'role_name',
      'role_code',
      'role_description',
      'selected_permission_codes',
    ]);

    $this->role_scope = RoleScope::Workspace->value;
  }

  /**
   * @return array{role_name: string, role_code: string, role_scope: string, role_description: string, selected_permission_codes: array<int, string>}
   */
  private function validateRole(): array
  {
    $this->role_code = Str::of($this->role_code ?: $this->role_name)
      ->slug()
      ->toString();

    $validated = $this->validate([
      'role_name' => ['required', 'string', 'max:255'],
      'role_code' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9._-]+$/'],
      'role_scope' => ['required', Rule::in([RoleScope::Workspace->value, RoleScope::Project->value])],
      'role_description' => ['nullable', 'string', 'max:1000'],
      'selected_permission_codes' => ['array'],
      'selected_permission_codes.*' => ['string', Rule::in(PermissionCatalog::codes())],
    ]);

    $duplicateExists = $this->manageableRolesQuery()
      ->where('code', $validated['role_code'])
      ->where('scope', $validated['role_scope'])
      ->when($this->editing_role_id, fn(Builder $query) => $query->where('id', '!=', $this->editing_role_id))
      ->exists();

    if ($duplicateExists) {
      throw ValidationException::withMessages([
        'role_code' => __('A role with this code already exists for this scope.'),
      ]);
    }

    return $validated;
  }

  private function roleIsAssigned(Role $role): bool
  {
    return DB::table('workspace_members')->where('access_role_id', $role->id)->exists()
      || DB::table('project_members')->where('access_role_id', $role->id)->exists()
      || DB::table('user_roles')->where('role_id', $role->id)->exists();
  }
}; ?>

<section class="w-full h-full min-h-0">
  <x-pages::settings.layout :full-width="true" :content-scroll="false">
    <form wire:submit="savePermissions" class="grid w-full gap-4 xl:grid-cols-[320px_minmax(0,1fr)] h-full min-h-0">
      <aside
        class="flex h-full min-h-0 min-w-0 flex-col overflow-hidden rounded-md border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
          <flux:modal.trigger name="create-role">
            <button type="button" wire:click="createNewRole"
              class="inline-flex cursor-pointer items-center gap-1.5 text-sm text-[#006de5] hover:underline dark:text-[#8dc5ff]"
              data-test="new-role-button">
              <flux:icon name="plus-circle" class="size-4" />
              <span>{{ __('Add role') }}</span>
            </button>
          </flux:modal.trigger>
        </div>

        <div class="min-h-0 flex-1 space-y-1 overflow-y-auto p-3">
          @forelse ($this->roles as $role)
            <div wire:key="role-{{ $role->id }}"
              class="group grid min-h-12 grid-cols-[minmax(0,1fr)_auto] items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors {{ $editing_role_id === $role->id ? 'bg-zinc-50 dark:bg-zinc-900' : 'hover:bg-zinc-50 dark:hover:bg-zinc-900' }}"
              data-test="role-row">
              <button type="button" wire:click="editRole({{ $role->id }})" class="min-w-0 cursor-pointer text-left">
                <span
                  class="block truncate font-medium text-zinc-900 transition-colors group-hover:text-[#006de5] dark:text-zinc-100">{{ $role->name }}</span>
                <span
                  class="block truncate text-xs text-zinc-500 transition-colors group-hover:text-zinc-700 dark:group-hover:text-zinc-300">{{ $role->description ?: __('No description') }}</span>
              </button>

              <flux:modal.trigger name="edit-role">
                <button type="button" wire:click="editRole({{ $role->id }})"
                  class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-800 dark:hover:text-zinc-300"
                  data-test="edit-role-button" title="{{ __('Edit role') }}">
                  <flux:icon name="pencil" class="size-3.5" />
                </button>
              </flux:modal.trigger>
            </div>
          @empty
            <div class="px-3 py-8 text-center text-sm text-zinc-500">
              {{ __('No roles yet') }}
            </div>
          @endforelse
        </div>
      </aside>

      <div
        class="flex h-full min-h-0 min-w-0 flex-col overflow-hidden rounded-md border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-950">
        <div
          class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700 flex-shrink-0">
          @if ($editing_role_id)
            <div class="flex items-center gap-2">
              <flux:text class="text-xs text-zinc-500">{{ __('Selected') }}</flux:text>
              <flux:badge>{{ count($selected_permission_codes) }}</flux:badge>
            </div>
          @endif
        </div>

        <section class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto">
          @if ($editing_role_id)
            <div class="space-y-1 p-3">
              @foreach ($this->permissionGroups as $group => $permissions)
                <details wire:key="permission-group-{{ Str::slug($group) }}"
                  class="group rounded-md border border-zinc-200 dark:border-zinc-800" open>
                  <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2.5 text-sm font-medium text-zinc-900 hover:bg-zinc-50 dark:text-zinc-100 dark:hover:bg-black">
                    <span>{{ __($group) }}</span>
                    <span class="flex items-center gap-2 text-xs font-normal text-zinc-500">
                      <span>{{ count($permissions) }}</span>
                      <flux:icon.chevron-down class="size-4 transition-transform group-open:rotate-180" />
                    </span>
                  </summary>

                  <div class="divide-y divide-zinc-200 border-t border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                    @foreach ($permissions as $permission)
                      <label wire:key="permission-{{ $permission['code'] }}"
                        class="grid min-h-10 grid-cols-[minmax(0,1fr)_auto] items-center gap-4 px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-black">
                        <span class="min-w-0">
                          <span
                            class="block truncate font-medium text-zinc-900 dark:text-zinc-100">{{ __($permission['name']) }}</span>
                          <span class="block text-xs leading-5 text-zinc-500">{{ __($permission['description']) }}</span>
                        </span>

                        <span class="relative inline-flex h-5 w-9 shrink-0 items-center">
                          <input type="checkbox" wire:model="selected_permission_codes" value="{{ $permission['code'] }}"
                            class="peer sr-only">
                          <span
                            class="absolute inset-0 rounded-full bg-zinc-200 transition peer-checked:bg-[#006de5] dark:bg-zinc-700"></span>
                          <span
                            class="absolute left-0.5 size-4 rounded-full bg-white shadow-sm transition peer-checked:translate-x-4"></span>
                        </span>
                      </label>
                    @endforeach
                  </div>
                </details>
              @endforeach
            </div>
          @else
            <div class="flex flex-col items-center justify-center gap-2 px-4 py-16 text-sm text-zinc-500 flex-1">
              <flux:icon name="shield-check" class="size-10 !text-zinc-300 dark:!text-zinc-700" />
              <span>{{ __('Select a role to view permissions') }}</span>
            </div>
          @endif
        </section>

        @if ($editing_role_id)
          <div class="flex justify-end border-t border-zinc-200 px-4 py-3 dark:border-zinc-700 flex-shrink-0">
            <flux:button type="submit" size="sm" variant="primary" data-test="save-role-button">
              {{ __('Save permissions') }}
            </flux:button>
          </div>
        @endif
      </div>
    </form>

    <flux:modal name="create-role"
      :show="$role_form_context === 'create' && ($errors->has('role_name') || $errors->has('role_code') || $errors->has('role_scope') || $errors->has('role_description'))"
      focusable class="max-w-lg">
      <form wire:submit="createRole" class="space-y-5">
        <div class="space-y-1">
          <flux:subheading>{{ __('Set a clear role name and a short description') }}</flux:subheading>
        </div>

        <div class="grid gap-4">
          <flux:input wire:model.live="role_name" :label="__('Role name')" required autofocus
            data-test="role-name-input" />

          <flux:textarea wire:model="role_description" :label="__('Short description')" rows="3" />

          <x-ui.select model="role_scope" :value="$role_scope" :label="__('Scope')" :options="[
    'workspace' => __('Workspace'),
    'project' => __('Project'),
  ]" required data-test="role-scope-select" />
        </div>

        <div class="flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
          <flux:modal.close>
            <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
          </flux:modal.close>

          <flux:button type="submit" size="sm" variant="primary" data-test="create-role-submit">
            {{ __('Add role') }}
          </flux:button>
        </div>
      </form>
    </flux:modal>

    <flux:modal name="edit-role"
      :show="$role_form_context === 'edit' && ($errors->has('editing_role_id') || $errors->has('role_name') || $errors->has('role_code') || $errors->has('role_scope') || $errors->has('role_description'))"
      focusable class="max-w-lg">
      <form wire:submit="updateRole" class="space-y-5">
        <div class="space-y-1">
          <flux:subheading>{{ __('Update the role name and short description') }}</flux:subheading>
        </div>

        <div class="grid gap-4">
          <flux:input wire:model.live="role_name" :label="__('Role name')" required data-test="edit-role-name-input" />

          <flux:textarea wire:model="role_description" :label="__('Short description')" rows="3" />

          <x-ui.select model="role_scope" :value="$role_scope" :label="__('Scope')" :options="[
    'workspace' => __('Workspace'),
    'project' => __('Project'),
  ]" required data-test="edit-role-scope-select" />
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
          <flux:button type="button" size="sm" variant="danger" wire:click="deleteRole" :disabled="! $editing_role_id"
            data-test="delete-role-button">
            {{ __('Delete role') }}
          </flux:button>

          <div class="flex justify-end gap-2">
            <flux:modal.close>
              <flux:button type="button" size="sm" variant="filled">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>

            <flux:button type="submit" size="sm" variant="primary" data-test="update-role-submit">
              {{ __('Save changes') }}
            </flux:button>
          </div>
        </div>
      </form>
    </flux:modal>
  </x-pages::settings.layout>
</section>
