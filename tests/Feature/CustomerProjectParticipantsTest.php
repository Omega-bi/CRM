<?php

use App\Enums\ProjectRole;
use App\Models\Project;
use Modules\Workspace\Models\Workspace;
use Modules\Access\Actions\AssignProjectRole;
use Modules\Access\Actions\CreatePermission;
use Modules\Access\Actions\CreateRole;
use Modules\Access\Actions\SyncRolePermissions;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Services\PermissionResolver;
use Modules\Customer\Actions\AttachProjectCustomer;
use Modules\Customer\Actions\CreateCustomer;
use Modules\Customer\Actions\CreateCustomerContact;
use Modules\Customer\Actions\GrantCustomerContactSystemAccess;
use Modules\Customer\Models\CustomerContact;
use Modules\Customer\Models\Customer;

test('customers are scoped to workspaces and can be connected to projects', function () {
    $ostara = Workspace::factory()->create(['name' => 'Ostara Projects']);
    $anotherWorkspace = Workspace::factory()->create(['name' => 'Another Workspace']);

    $school = Project::factory()->for($ostara)->create(['name' => 'School Construction']);
    $residential = Project::factory()->for($anotherWorkspace)->create(['name' => 'Residential Construction']);

    $akimat = app(CreateCustomer::class)->handle([
        'workspace_id' => $ostara->id,
        'name' => 'City Akimat',
    ]);
    $educationDepartment = app(CreateCustomer::class)->handle([
        'workspace_id' => $ostara->id,
        'name' => 'Education Department',
    ]);
    $privateCustomer = app(CreateCustomer::class)->handle([
        'workspace_id' => $anotherWorkspace->id,
        'name' => 'Private Investor',
    ]);

    app(AttachProjectCustomer::class)->handle($school, $akimat, 'owner');
    app(AttachProjectCustomer::class)->handle($school, $educationDepartment, 'stakeholder');
    app(AttachProjectCustomer::class)->handle($residential, $privateCustomer, 'owner');

    expect($school->customers()->pluck('crm_customers.name')->all())
        ->toBe(['City Akimat', 'Education Department'])
        ->and($residential->customers()->pluck('crm_customers.name')->all())
        ->toBe(['Private Investor']);
});

test('project cannot be connected to a customer from another workspace', function () {
    $workspace = Workspace::factory()->create();
    $anotherWorkspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();
    $foreignCustomer = app(CreateCustomer::class)->handle([
        'workspace_id' => $anotherWorkspace->id,
        'name' => 'Foreign Workspace Customer',
    ]);

    app(AttachProjectCustomer::class)->handle($project, $foreignCustomer);
})->throws(\InvalidArgumentException::class);

test('customer contact can receive access and participate in a project', function () {
    $workspace = Workspace::factory()->create();
    $project = Project::factory()->for($workspace)->create();
    $customer = app(CreateCustomer::class)->handle([
        'workspace_id' => $workspace->id,
        'name' => 'City Akimat',
    ]);

    app(AttachProjectCustomer::class)->handle($project, $customer, 'owner');

    $contact = app(CreateCustomerContact::class)->handle($customer, [
        'full_name' => 'Customer Approver',
        'email' => 'approver@example.com',
        'phone' => '+77000000001',
        'position' => 'Approver',
    ]);

    $user = app(GrantCustomerContactSystemAccess::class)->handle($contact, password: 'password');

    app(CreatePermission::class)->handle([
        'code' => 'acts.approve',
        'name' => 'Approve acts',
        'group' => 'acts',
    ]);

    $approver = app(CreateRole::class)->handle([
        'name' => 'Approver',
        'scope' => RoleScope::Project,
        'project_id' => $project->id,
    ]);

    app(SyncRolePermissions::class)->handle($approver, ['acts.approve']);

    $project->members()->attach($user, [
        'workspace_id' => $workspace->id,
        'role' => ProjectRole::Observer->value,
    ]);

    app(AssignProjectRole::class)->handle($project, $user, $approver);

    expect($contact->fresh()->hasSystemAccess())->toBeTrue()
        ->and($user->customerContact?->id)->toBe($contact->id)
        ->and(app(PermissionResolver::class)->userCanInProject($user, $project, 'acts.approve'))->toBeTrue();
});

test('customer module models point to current customer-side tables', function () {
    expect((new Customer)->getTable())->toBe('crm_customers')
        ->and((new CustomerContact)->getTable())->toBe('crm_customer_contacts');
});
