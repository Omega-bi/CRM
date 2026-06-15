<?php

use App\Models\User;
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
        ->set('staff_position_planned_count', '2')
        ->set('staff_position_sort_order', '1')
        ->set('staff_position_is_active', '1')
        ->call('createStaffPosition')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('organization_staff_positions', [
        'department_id' => $department->id,
        'name' => 'Technical supervisor',
        'planned_count' => 2,
        'sort_order' => 1,
        'is_active' => true,
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

test('workers settings page can edit an employee', function () {
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
        ->set('editing_employee_staff_position_id', $staffPosition->id)
        ->call('updateEmployee')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'full_name' => 'Иван Сергеев',
        'phone' => '+7 700 999 88 77',
        'email' => 'ivan.sergeev@example.com',
        'position' => 'Начальник участка',
        'staff_position_id' => $staffPosition->id,
    ]);

    $this->assertDatabaseHas('department_employee', [
        'department_id' => $department->id,
        'employee_id' => $employee->id,
    ]);
});
