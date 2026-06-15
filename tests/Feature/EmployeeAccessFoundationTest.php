<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use Modules\Access\Actions\AssignProjectRole;
use Modules\Access\Actions\AssignSystemRole;
use Modules\Access\Actions\AssignWorkspaceRole;
use Modules\Access\Actions\CreatePermission;
use Modules\Access\Actions\CreateRole;
use Modules\Access\Actions\SyncRolePermissions;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Enums\SystemRoleCode;
use Modules\Access\Models\Role;
use Modules\Access\Services\PermissionResolver;
use Modules\Employee\Actions\CreateEmployee;
use Modules\Employee\Actions\GrantEmployeeSystemAccess;
use Modules\Employee\Models\Employee;
use Modules\Workspace\Models\Workspace;

test('employee can exist without a user account', function () {
    $employee = app(CreateEmployee::class)->handle([
        'full_name' => 'Project Foreman',
        'phone' => '+77000000000',
        'email' => 'foreman@example.com',
        'position' => 'Foreman',
    ]);

    expect($employee)->toBeInstanceOf(Employee::class)
        ->and($employee->user_id)->toBeNull()
        ->and($employee->hasSystemAccess())->toBeFalse();
});

test('employee can be granted a user account later', function () {
    $employee = app(CreateEmployee::class)->handle([
        'full_name' => 'Technical Supervisor',
        'email' => 'supervisor@example.com',
        'position' => 'Technical Supervisor',
    ]);

    $user = app(GrantEmployeeSystemAccess::class)->handle($employee, password: 'password');

    expect($user)->toBeInstanceOf(User::class)
        ->and($employee->fresh()->user_id)->toBe($user->id)
        ->and($employee->fresh()->hasSystemAccess())->toBeTrue();
});

test('system admin role is assigned separately from employee profile', function () {
    $user = User::factory()->create();
    $adminRole = app(CreateRole::class)->handle([
        'name' => 'Admin',
        'code' => SystemRoleCode::Admin->value,
        'scope' => RoleScope::System,
        'is_system' => true,
    ]);

    app(AssignSystemRole::class)->handle($user, $adminRole);

    expect(app(PermissionResolver::class)->userHasSystemRole($user, SystemRoleCode::Admin))->toBeTrue();
});

test('workspace role permissions are independent from project role permissions', function () {
    $user = User::factory()->create();
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();

    $workspace->members()->attach($user, ['role' => WorkspaceRole::Member->value]);
    $project->members()->attach($user, [
        'workspace_id' => $workspace->id,
        'role' => ProjectRole::Foreman->value,
    ]);

    app(CreatePermission::class)->handle([
        'code' => 'projects.manage',
        'name' => 'Manage projects',
        'group' => 'projects',
    ]);
    app(CreatePermission::class)->handle([
        'code' => 'daily-logs.create',
        'name' => 'Create daily logs',
        'group' => 'daily-logs',
    ]);

    $projectManager = app(CreateRole::class)->handle([
        'name' => 'Project Manager',
        'scope' => RoleScope::Workspace,
        'workspace_id' => $workspace->id,
    ]);
    $foreman = app(CreateRole::class)->handle([
        'name' => 'Foreman',
        'scope' => RoleScope::Project,
        'project_id' => $project->id,
    ]);

    app(SyncRolePermissions::class)->handle($projectManager, ['projects.manage']);
    app(SyncRolePermissions::class)->handle($foreman, ['daily-logs.create']);

    app(AssignWorkspaceRole::class)->handle($workspace, $user, $projectManager);
    app(AssignProjectRole::class)->handle($project, $user, $foreman);

    $resolver = app(PermissionResolver::class);

    expect($resolver->userCanInWorkspace($user, $workspace, 'projects.manage'))->toBeTrue()
        ->and($resolver->userCanInWorkspace($user, $workspace, 'daily-logs.create'))->toBeFalse()
        ->and($resolver->userCanInProject($user, $project, 'daily-logs.create'))->toBeTrue()
        ->and($resolver->userCanInProject($user, $project, 'projects.manage'))->toBeFalse();
});

test('access module models point to current database tables', function () {
    expect((new Role)->getTable())->toBe('roles')
        ->and((new Employee)->getTable())->toBe('employees');
});
