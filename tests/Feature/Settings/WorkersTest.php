<?php

use App\Enums\ProjectRole;
use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\StaffPosition;

test('workers settings page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('workers.index'))
        ->assertOk()
        ->assertSee('Company departments')
        ->assertSee('Company staff')
        ->assertSee('Add');
});

test('workers settings page lists departments', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Foreman',
        'planned_count' => 3,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->assertSee('Company departments')
        ->assertSee('Production')
        ->assertSee('Company staff')
        ->assertSee('Search');
});

test('workers settings page initially shows only three root departments', function () {
    $this->actingAs(User::factory()->create());

    foreach (range(1, 4) as $index) {
        Department::create([
            'name' => 'Root department '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'sort_order' => $index,
            'is_active' => true,
        ]);
    }

    $component = Livewire::test('pages::settings.workers')
        ->assertSee('Root department 01')
        ->assertSee('Root department 02')
        ->assertSee('Root department 03');

    expect($component->html())->not->toContain('data-department-tree-name="Root department 04"');

    $component
        ->call('showAllDepartments')
        ->assertSet('show_all_departments', true);
});

test('workers settings page lists staff positions', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Foreman',
        'planned_count' => 3,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->assertSee('Positions')
        ->assertSee('Foreman');
});

test('workers settings page lists employees', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $staffPosition = StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Foreman',
        'planned_count' => 3,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Employee::create([
        'full_name' => 'Алибек Нуртаев',
        'phone' => '+7 701 000 00 00',
        'email' => 'alibek@example.com',
        'position' => 'Прораб',
        'staff_position_id' => $staffPosition->id,
        'status' => EmployeeStatus::Active,
    ]);

    Livewire::test('pages::settings.workers')
        ->assertSee('Company departments')
        ->assertSee('Алибек Нуртаев')
        ->assertSee('Production')
        ->assertSee('Foreman');
});

test('workers settings page paginates employees', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $staffPosition = StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Foreman',
        'planned_count' => 12,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    foreach (range(1, 12) as $index) {
        $employee = Employee::create([
            'full_name' => 'Employee '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'email' => 'employee'.$index.'@example.com',
            'position' => 'Foreman',
            'staff_position_id' => $staffPosition->id,
            'status' => EmployeeStatus::Active,
        ]);

        $employee->departments()->sync([$department->id]);
    }

    $component = Livewire::test('pages::settings.workers')
        ->assertSee('Company staff')
        ->assertSee('Employee 10');

    expect($component->instance()->workers->pluck('full_name')->all())
        ->not->toContain('Employee 11')
        ->not->toContain('Employee 12');

    $component
        ->call('setWorkersPage', 2)
        ->assertSet('workers_page', 2);

    expect($component->instance()->workers->pluck('full_name')->all())
        ->toContain('Employee 11')
        ->toContain('Employee 12');
});

test('workers settings create department modal shows all selectable employees', function () {
    $this->actingAs(User::factory()->create());

    foreach (range(1, 12) as $index) {
        Employee::create([
            'full_name' => 'Modal Employee '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'email' => 'modal.employee'.$index.'@example.com',
            'status' => EmployeeStatus::Active,
        ]);
    }

    $component = Livewire::test('pages::settings.workers');

    expect($component->instance()->departmentWorkers->pluck('full_name')->all())
        ->toContain('Modal Employee 01')
        ->toContain('Modal Employee 10')
        ->toContain('Modal Employee 11')
        ->toContain('Modal Employee 12');
});

test('workers settings edit department modal shows all selectable employees', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    foreach (range(1, 12) as $index) {
        Employee::create([
            'full_name' => 'Edit Modal Employee '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'email' => 'edit.modal.employee'.$index.'@example.com',
            'status' => EmployeeStatus::Active,
        ]);
    }

    $component = Livewire::test('pages::settings.workers')
        ->call('editDepartment', $department->id);

    expect($component->instance()->editingDepartmentWorkers->pluck('full_name')->all())
        ->toContain('Edit Modal Employee 01')
        ->toContain('Edit Modal Employee 10')
        ->toContain('Edit Modal Employee 11')
        ->toContain('Edit Modal Employee 12');
});

test('workers settings edit employee modal paginates departments', function () {
    $this->actingAs(User::factory()->create());

    foreach (range(1, 12) as $index) {
        Department::create([
            'name' => 'Employee Modal Department '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'sort_order' => $index,
            'is_active' => true,
        ]);
    }

    $employee = Employee::create([
        'full_name' => 'Иван Петров',
        'email' => 'ivan.modal@example.com',
        'status' => EmployeeStatus::Active,
    ]);

    $component = Livewire::test('pages::settings.workers')
        ->call('editEmployee', $employee->id);

    expect($component->instance()->editingEmployeeDepartments->pluck('name')->all())
        ->toContain('Employee Modal Department 10')
        ->not->toContain('Employee Modal Department 11');

    $component
        ->call('setEditingEmployeeDepartmentsPage', 2)
        ->assertSet('editing_employee_departments_page', 2);

    expect($component->instance()->editingEmployeeDepartments->pluck('name')->all())
        ->toContain('Employee Modal Department 11')
        ->toContain('Employee Modal Department 12');
});

test('workers settings page can create a department', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::settings.workers')
        ->set('department_name', 'Quality control')
        ->set('department_sort_order', '2')
        ->set('department_is_active', '1')
        ->call('createDepartment')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('organization_departments', [
        'name' => 'Quality control',
        'sort_order' => 2,
        'is_active' => true,
    ]);
});

test('workers settings page can create a child department and assign employees', function () {
    $this->actingAs(User::factory()->create());

    $parentDepartment = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $employee = Employee::create([
        'full_name' => 'Иван Петров',
        'phone' => '+7 700 111 22 33',
        'email' => 'ivan@example.com',
        'position' => 'Прораб',
        'status' => EmployeeStatus::Active,
    ]);

    Livewire::test('pages::settings.workers')
        ->set('department_name', 'Logistics')
        ->set('department_sort_order', '3')
        ->set('department_is_active', '1')
        ->set('department_parent_id', $parentDepartment->id)
        ->set('department_employee_ids', [$employee->id])
        ->call('createDepartment')
        ->assertHasNoErrors();

    $department = Department::query()
        ->where('name', 'Logistics')
        ->firstOrFail();

    $this->assertDatabaseHas('organization_departments', [
        'name' => 'Logistics',
        'parent_id' => $parentDepartment->id,
        'sort_order' => 3,
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $department->id,
        'employee_id' => $employee->id,
    ]);
});

test('workers settings page can move a department with its employees', function () {
    $this->actingAs(User::factory()->create());

    $administration = Department::create([
        'name' => 'Administration',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $technical = Department::create([
        'name' => 'Technical supervision',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $accounting = Department::create([
        'name' => 'Accounting',
        'parent_id' => $technical->id,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $employee = Employee::create([
        'full_name' => 'Аманжолов Балгын',
        'phone' => '+7 700 111 22 33',
        'email' => 'balgyn@example.com',
        'position' => 'Бухгалтер',
        'status' => EmployeeStatus::Active,
    ]);

    $accounting->employees()->sync([$employee->id]);

    Livewire::test('pages::settings.workers')
        ->call('moveDepartment', $accounting->id, $administration->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('organization_departments', [
        'id' => $accounting->id,
        'parent_id' => $administration->id,
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $accounting->id,
        'employee_id' => $employee->id,
    ]);
});

test('workers settings page cannot move a department inside its own child', function () {
    $this->actingAs(User::factory()->create());

    $parent = Department::create([
        'name' => 'Parent',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $child = Department::create([
        'name' => 'Child',
        'parent_id' => $parent->id,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->call('moveDepartment', $parent->id, $child->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('organization_departments', [
        'id' => $parent->id,
        'parent_id' => null,
    ]);
});

test('workers settings page can create a staff position', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->set('staff_position_department_id', $department->id)
        ->set('staff_position_name', 'Technical supervisor')
        ->set('staff_position_is_active', '1')
        ->call('createStaffPosition')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('organization_staff_positions', [
        'department_id' => $department->id,
        'name' => 'Technical supervisor',
        'planned_count' => 0,
        'sort_order' => 1,
        'is_active' => true,
    ]);
});

test('workers settings page can search staff positions', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Technician',
        'planned_count' => 1,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Engineer',
        'planned_count' => 1,
        'sort_order' => 2,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->set('staff_position_search', 'Techn')
        ->assertSee('Technician')
        ->assertDontSee('Engineer');
});

test('workers settings page paginates staff positions', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    foreach (range(1, 12) as $index) {
        StaffPosition::create([
            'department_id' => $department->id,
            'name' => 'Position '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
            'planned_count' => 1,
            'sort_order' => $index,
            'is_active' => true,
        ]);
    }

    Livewire::test('pages::settings.workers')
        ->assertSee('Positions')
        ->assertSee('Position 10')
        ->assertDontSee('Position 11')
        ->call('setStaffPositionsPage', 2)
        ->assertSet('staff_positions_page', 2)
        ->assertSee('Position 11')
        ->assertSee('Position 12');
});

test('workers settings page can update a staff position', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Production',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $departmentSecond = Department::create([
        'name' => 'Operations',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $position = StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Technician',
        'planned_count' => 1,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->call('editStaffPosition', $position->id)
        ->set('editing_staff_position_department_id', $departmentSecond->id)
        ->set('editing_staff_position_name', 'Senior technician')
        ->set('editing_staff_position_is_active', '0')
        ->call('updateStaffPosition')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('organization_staff_positions', [
        'id' => $position->id,
        'department_id' => $departmentSecond->id,
        'name' => 'Senior technician',
        'planned_count' => 1,
        'sort_order' => 1,
        'is_active' => false,
    ]);
});

test('workers settings page can create an employee', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Administration',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->set('full_name', 'Иван Петров')
        ->set('phone', '+7 700 111 22 33')
        ->set('email', 'ivan@example.com')
        ->set('position', 'Прораб')
        ->set('employee_department_id', $department->id)
        ->call('createEmployee')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employees', [
        'full_name' => 'Иван Петров',
        'phone' => '+7 700 111 22 33',
        'email' => 'ivan@example.com',
        'position' => 'Прораб',
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $department->id,
    ]);
});

test('workers settings page can create an employee with a staff position', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Administration',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $staffPosition = StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Director',
        'planned_count' => 1,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Livewire::test('pages::settings.workers')
        ->set('full_name', 'Иван Петров')
        ->set('phone', '+7 700 111 22 33')
        ->set('email', 'ivan@example.com')
        ->set('employee_staff_position_id', $staffPosition->id)
        ->assertSet('employee_department_id', $department->id)
        ->assertSet('position', 'Director')
        ->call('createEmployee')
        ->assertHasNoErrors();

    $employee = Employee::query()
        ->where('email', 'ivan@example.com')
        ->firstOrFail();

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'position' => 'Director',
        'staff_position_id' => $staffPosition->id,
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $department->id,
        'employee_id' => $employee->id,
    ]);
});

test('workers settings page can edit an employee', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Administration',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $secondaryDepartment = Department::create([
        'name' => 'Operations',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $staffPosition = StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Director',
        'planned_count' => 1,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $employee = Employee::create([
        'full_name' => 'Иван Петров',
        'phone' => '+7 700 111 22 33',
        'email' => 'ivan@example.com',
        'position' => 'Прораб',
        'staff_position_id' => $staffPosition->id,
        'status' => EmployeeStatus::Active,
    ]);

    $employee->departments()->sync([$department->id]);

    Livewire::test('pages::settings.workers')
        ->call('editEmployee', $employee->id)
        ->set('editing_employee_full_name', 'Иван Сергеев')
        ->set('editing_employee_phone', '+7 700 999 88 77')
        ->set('editing_employee_email', 'ivan.sergeev@example.com')
        ->set('editing_employee_position', 'Начальник участка')
        ->set('editing_employee_department_id', $department->id)
        ->set('editing_employee_department_ids', [$department->id, $secondaryDepartment->id])
        ->set('editing_employee_staff_position_id', $staffPosition->id)
        ->call('updateEmployee')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'full_name' => 'Иван Сергеев',
        'phone' => '+7 700 999 88 77',
        'email' => 'ivan.sergeev@example.com',
        'position' => 'Director',
        'staff_position_id' => $staffPosition->id,
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $department->id,
        'employee_id' => $employee->id,
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $secondaryDepartment->id,
        'employee_id' => $employee->id,
    ]);
});

test('workers settings employee edit keeps department state consistent', function () {
    $this->actingAs(User::factory()->create());

    $department = Department::create([
        'name' => 'Administration',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $secondaryDepartment = Department::create([
        'name' => 'Operations',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $staffPosition = StaffPosition::create([
        'department_id' => $department->id,
        'name' => 'Director',
        'planned_count' => 1,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $employee = Employee::create([
        'full_name' => 'Иван Петров',
        'phone' => '+7 700 111 22 33',
        'email' => 'ivan@example.com',
        'position' => 'Прораб',
        'staff_position_id' => $staffPosition->id,
        'status' => EmployeeStatus::Active,
    ]);

    $employee->departments()->sync([$department->id]);

    Livewire::test('pages::settings.workers')
        ->call('editEmployee', $employee->id)
        ->set('editing_employee_full_name', 'Иван Сергеев')
        ->set('editing_employee_department_ids', [$secondaryDepartment->id])
        ->assertSet('editing_employee_full_name', 'Иван Сергеев')
        ->assertSet('editing_employee_department_id', $secondaryDepartment->id)
        ->assertSet('editing_employee_staff_position_id', null)
        ->call('updateEmployee')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'full_name' => 'Иван Сергеев',
        'staff_position_id' => null,
    ]);

    $this->assertDatabaseMissing('department_employee', [
        'department_id' => $department->id,
        'employee_id' => $employee->id,
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $secondaryDepartment->id,
        'employee_id' => $employee->id,
    ]);
});

test('workers settings page can create an employee account', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner);

    $employee = Employee::create([
        'full_name' => 'Иван Петров',
        'phone' => '+7 700 111 22 33',
        'email' => 'ivan@example.com',
        'position' => 'Прораб',
        'status' => EmployeeStatus::Active,
    ]);

    Livewire::test('pages::settings.workers')
        ->call('editEmployee', $employee->id)
        ->call('openCreateEmployeeAccountModal')
        ->assertSet('account_employee_id', $employee->id)
        ->assertSet('account_name', 'Иван Петров')
        ->assertSet('account_email', 'ivan@example.com')
        ->set('account_password', 'password')
        ->set('account_password_confirmation', 'password')
        ->call('createEmployeeAccount')
        ->assertHasNoErrors();

    $user = User::query()
        ->where('email', 'ivan@example.com')
        ->firstOrFail();

    expect(Hash::check('password', $user->password))->toBeTrue()
        ->and($employee->fresh()->user_id)->toBe($user->id)
        ->and($user->fresh()->current_workspace_id)->toBe($owner->currentWorkspace->id);

    $this->assertDatabaseHas('workspace_members', [
        'workspace_id' => $owner->currentWorkspace->id,
        'user_id' => $user->id,
        'role' => WorkspaceRole::Member->value,
    ]);
});

test('workers settings page can delete an employee', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $department = Department::create([
        'name' => 'Administration',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $employeeUser = User::factory()->create();
    $employee = Employee::create([
        'user_id' => $employeeUser->id,
        'full_name' => 'Иван Петров',
        'phone' => '+7 700 111 22 33',
        'email' => 'ivan@example.com',
        'position' => 'Прораб',
        'status' => EmployeeStatus::Active,
    ]);

    $employee->departments()->sync([$department->id]);

    $project = Project::create([
        'workspace_id' => $user->currentWorkspace->id,
        'name' => 'Tower',
    ]);

    $project->members()->attach($employeeUser, [
        'workspace_id' => $user->currentWorkspace->id,
        'role' => ProjectRole::Observer->value,
    ]);

    Livewire::test('pages::settings.workers')
        ->call('editEmployee', $employee->id)
        ->call('deleteEmployee')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('employees', [
        'id' => $employee->id,
    ]);

    $this->assertDatabaseMissing('department_employee', [
        'department_id' => $department->id,
        'employee_id' => $employee->id,
    ]);

    $this->assertDatabaseMissing('project_members', [
        'project_id' => $project->id,
        'user_id' => $employeeUser->id,
    ]);
});
