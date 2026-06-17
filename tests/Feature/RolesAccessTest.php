<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use Modules\Access\Actions\AssignProjectRole;
use Modules\Access\Actions\AssignSystemRole;
use Modules\Access\Actions\AssignWorkspaceRole;
use Modules\Access\Actions\SyncAccessDefaults;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Enums\SystemRoleCode;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Services\PermissionResolver;

test('roles settings page is displayed for workspace owner', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('roles.index'))
        ->assertOk()
        ->assertSee('Roles &amp; permissions', false)
        ->assertSee('Create roles and assign precise access rights')
        ->assertSee('Add role')
        ->assertSee('Can create new roles and prepare access templates for users.');
});

test('roles settings page is displayed for system admin without current workspace', function () {
    $user = User::factory()->create();
    $user->forceFill(['current_workspace_id' => null])->save();

    app(SyncAccessDefaults::class)->handle();

    $adminRole = Role::query()
        ->where('scope', RoleScope::System)
        ->where('code', SystemRoleCode::Admin->value)
        ->firstOrFail();

    app(AssignSystemRole::class)->handle($user, $adminRole);

    $this->actingAs($user);

    $this->get(route('roles.index'))
        ->assertOk()
        ->assertSee('Roles &amp; permissions', false);
});

test('workspace owner can create role and assign permissions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::settings.roles')
        ->call('createNewRole')
        ->set('role_name', 'Project coordinator')
        ->set('role_description', 'Coordinates project access')
        ->set('role_scope', RoleScope::Workspace->value)
        ->set('selected_permission_codes', ['projects.view', 'projects.create'])
        ->call('createRole')
        ->assertHasNoErrors();

    $role = Role::query()
        ->where('workspace_id', $user->current_workspace_id)
        ->where('code', 'project-coordinator')
        ->firstOrFail();

    expect($role->scope)->toBe(RoleScope::Workspace)
        ->and($role->description)->toBe('Coordinates project access')
        ->and($role->permissions()->pluck('code')->sort()->values()->all())->toBe([
            'projects.create',
            'projects.view',
        ]);
});

test('workspace owner can update role permissions', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    app(SyncAccessDefaults::class)->handle();

    $role = Role::create([
        'name' => 'Viewer',
        'code' => 'viewer',
        'scope' => RoleScope::Workspace,
        'workspace_id' => $user->current_workspace_id,
    ]);

    $role->permissions()->sync([
        Permission::query()->where('code', 'projects.view')->value('id'),
    ]);

    Livewire::test('pages::settings.roles')
        ->call('editRole', $role->id)
        ->set('selected_permission_codes', ['projects.view', 'customers.view'])
        ->call('savePermissions')
        ->assertHasNoErrors();

    expect($role->fresh()->permissions()->pluck('code')->sort()->values()->all())->toBe([
        'customers.view',
        'projects.view',
    ]);
});

test('workspace owner can update role details', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    app(SyncAccessDefaults::class)->handle();

    $role = Role::create([
        'name' => 'Viewer',
        'code' => 'viewer',
        'scope' => RoleScope::Workspace,
        'workspace_id' => $user->current_workspace_id,
        'description' => 'Can view projects',
    ]);

    Livewire::test('pages::settings.roles')
        ->call('editRole', $role->id)
        ->set('role_name', 'Project observer')
        ->set('role_description', 'Reads project data without edits')
        ->call('updateRole')
        ->assertHasNoErrors();

    expect($role->fresh())
        ->name->toBe('Project observer')
        ->code->toBe('viewer')
        ->description->toBe('Reads project data without edits');
});

test('custom workspace role grants assigned permissions only in that workspace', function () {
    $owner = User::factory()->create();
    $user = User::factory()->create();
    $workspace = $owner->currentWorkspace;

    app(SyncAccessDefaults::class)->handle();

    $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);

    $role = Role::create([
        'name' => 'Customers operator',
        'code' => 'customers-operator',
        'scope' => RoleScope::Workspace,
        'workspace_id' => $workspace->id,
    ]);

    $role->permissions()->sync([
        Permission::query()->where('code', 'customers.view')->value('id'),
    ]);

    app(AssignWorkspaceRole::class)->handle($workspace, $user, $role);

    $resolver = app(PermissionResolver::class);

    expect($resolver->userCanInWorkspace($user, $workspace, 'customers.view'))->toBeTrue()
        ->and($resolver->userCanInWorkspace($user, $workspace, 'customers.delete'))->toBeFalse();
});

test('custom project role stays independent from workspace role permissions', function () {
    $user = User::factory()->create();
    $workspace = $user->currentWorkspace;
    $project = Project::factory()->for($workspace)->create();

    app(SyncAccessDefaults::class)->handle();

    $project->members()->attach($user, [
        'workspace_id' => $workspace->id,
        'role' => ProjectRole::Observer->value,
    ]);

    $projectRole = Role::create([
        'name' => 'Site foreman',
        'code' => 'site-foreman',
        'scope' => RoleScope::Project,
        'workspace_id' => $workspace->id,
    ]);

    $projectRole->permissions()->sync([
        Permission::query()->where('code', 'daily-logs.create')->value('id'),
    ]);

    app(AssignProjectRole::class)->handle($project, $user, $projectRole);

    $resolver = app(PermissionResolver::class);

    expect($resolver->userCanInProject($user, $project, 'daily-logs.create'))->toBeTrue()
        ->and($resolver->userCanInProject($user, $project, 'projects.manage'))->toBeFalse();
});

test('system admin role grants every known permission', function () {
    $user = User::factory()->create();

    app(SyncAccessDefaults::class)->handle();

    $adminRole = Role::query()
        ->where('scope', RoleScope::System)
        ->where('code', SystemRoleCode::Admin->value)
        ->firstOrFail();

    app(AssignSystemRole::class)->handle($user, $adminRole);

    expect(app(PermissionResolver::class)->userCan($user, 'roles.manage'))->toBeTrue()
        ->and(app(PermissionResolver::class)->userCan($user, 'customers.delete'))->toBeTrue();
});
