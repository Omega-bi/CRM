<?php

use Modules\Access\AccessServiceProvider;
use Modules\Access\Actions\AssignProjectRole;
use Modules\Access\Actions\AssignSystemRole;
use Modules\Access\Actions\AssignWorkspaceRole;
use Modules\Access\Actions\CreatePermission;
use Modules\Access\Actions\CreateRole;
use Modules\Access\Actions\SyncRolePermissions;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Enums\SystemRoleCode;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Services\PermissionResolver;
use Modules\Customer\CustomerServiceProvider;
use Modules\Customer\Actions\AttachProjectCustomer;
use Modules\Customer\Models\Customer;
use Modules\Customer\Models\CustomerContact;
use Modules\Customer\Actions\CreateCustomerContact;
use Modules\Customer\Actions\GrantCustomerContactSystemAccess;
use Modules\Employee\Actions\CreateEmployee;
use Modules\Employee\Actions\GrantEmployeeSystemAccess;
use Modules\Employee\EmployeeServiceProvider;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Workspace\Actions\CreateWorkspace as ModuleCreateWorkspace;
use Modules\Workspace\Enums\WorkspacePermission;
use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Workspace;
use Modules\Workspace\Models\WorkspaceInvitation;
use Modules\Workspace\Models\WorkspaceMember;
use Modules\Workspace\Policies\WorkspacePolicy as ModuleWorkspacePolicy;
use Modules\Workspace\Rules\UniqueWorkspaceInvitation as ModuleUniqueWorkspaceInvitation;
use Modules\Workspace\Rules\WorkspaceName as ModuleWorkspaceName;
use Modules\Workspace\WorkspaceServiceProvider;

test('business modules are autoloaded', function () {
    expect(class_exists(WorkspaceServiceProvider::class))->toBeTrue()
        ->and(class_exists(AccessServiceProvider::class))->toBeTrue()
        ->and(class_exists(EmployeeServiceProvider::class))->toBeTrue()
        ->and(class_exists(CustomerServiceProvider::class))->toBeTrue()
        ->and(class_exists(Workspace::class))->toBeTrue()
        ->and(class_exists(WorkspaceMember::class))->toBeTrue()
        ->and(class_exists(WorkspaceInvitation::class))->toBeTrue()
        ->and(class_exists(ModuleCreateWorkspace::class))->toBeTrue()
        ->and(enum_exists(WorkspacePermission::class))->toBeTrue()
        ->and(enum_exists(WorkspaceRole::class))->toBeTrue()
        ->and(class_exists(ModuleWorkspacePolicy::class))->toBeTrue()
        ->and(class_exists(ModuleWorkspaceName::class))->toBeTrue()
        ->and(class_exists(ModuleUniqueWorkspaceInvitation::class))->toBeTrue()
        ->and(class_exists(Customer::class))->toBeTrue()
        ->and(class_exists(CustomerContact::class))->toBeTrue()
        ->and(class_exists(AttachProjectCustomer::class))->toBeTrue()
        ->and(class_exists(CreateCustomerContact::class))->toBeTrue()
        ->and(class_exists(GrantCustomerContactSystemAccess::class))->toBeTrue()
        ->and(class_exists(Role::class))->toBeTrue()
        ->and(class_exists(Permission::class))->toBeTrue()
        ->and(class_exists(Employee::class))->toBeTrue()
        ->and(enum_exists(RoleScope::class))->toBeTrue()
        ->and(enum_exists(SystemRoleCode::class))->toBeTrue()
        ->and(enum_exists(EmployeeStatus::class))->toBeTrue()
        ->and(class_exists(CreateRole::class))->toBeTrue()
        ->and(class_exists(CreatePermission::class))->toBeTrue()
        ->and(class_exists(SyncRolePermissions::class))->toBeTrue()
        ->and(class_exists(AssignSystemRole::class))->toBeTrue()
        ->and(class_exists(AssignWorkspaceRole::class))->toBeTrue()
        ->and(class_exists(AssignProjectRole::class))->toBeTrue()
        ->and(class_exists(PermissionResolver::class))->toBeTrue()
        ->and(class_exists(CreateEmployee::class))->toBeTrue()
        ->and(class_exists(GrantEmployeeSystemAccess::class))->toBeTrue();
});

test('module models point to current database tables', function () {
    expect((new Workspace)->getTable())->toBe('workspaces')
        ->and((new WorkspaceMember)->getTable())->toBe('workspace_members')
        ->and((new WorkspaceInvitation)->getTable())->toBe('workspace_invitations')
        ->and((new Customer)->getTable())->toBe('crm_customers')
        ->and((new CustomerContact)->getTable())->toBe('crm_customer_contacts')
        ->and((new Role)->getTable())->toBe('roles')
        ->and((new Permission)->getTable())->toBe('permissions')
        ->and((new Employee)->getTable())->toBe('employees');
});
