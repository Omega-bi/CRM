<?php

use App\Models\Project;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Str;
use Modules\Employee\Actions\CreateEmployee;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Organization\Actions\CreateDepartment;
use Modules\Organization\Actions\CreateStaffPosition;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\StaffPosition;

new #[Title('Workers settings')] class extends Component {
  public string $search = '';

  public string $department_name = '';
  public string $department_sort_order = '0';
  public string $department_is_active = '1';
  public ?int $department_parent_id = null;
  public array $department_employee_ids = [];
  public string $department_employee_search = '';
  public string $editing_department_employee_search = '';
  public bool $show_all_departments = false;
  public ?int $editing_department_id = null;
  public string $editing_department_name = '';
  public string $editing_department_sort_order = '0';
  public string $editing_department_is_active = '1';
  public ?int $editing_department_parent_id = null;
  public array $editing_department_employee_ids = [];

  public string $staff_position_name = '';
  public string $staff_position_planned_count = '0';
  public string $staff_position_sort_order = '0';
  public string $staff_position_is_active = '1';
  public ?int $staff_position_department_id = null;

  public string $full_name = '';
  public string $phone = '';
  public string $email = '';
  public string $position = '';
  public ?int $employee_department_id = null;

  public ?int $editing_employee_id = null;
  public string $editing_employee_full_name = '';
  public string $editing_employee_phone = '';
  public string $editing_employee_email = '';
  public string $editing_employee_position = '';
  public ?int $editing_employee_department_id = null;
  public ?int $editing_employee_staff_position_id = null;
  public array $editing_employee_project_ids = [];

  #[Computed]
  public function departments(): Collection
  {
    $loadChildren = function ($query) use (&$loadChildren): void {
      $query
        ->select(['id', 'parent_id', 'name', 'sort_order', 'is_active', 'created_at'])
        ->with([
          'employees:id,full_name,email',
          'children' => $loadChildren,
        ])
        ->orderBy('sort_order')
        ->orderBy('name');
    };

    return Department::query()
      ->select(['id', 'parent_id', 'name', 'sort_order', 'is_active', 'created_at'])
      ->with([
        'employees:id,full_name,email',
        'children' => $loadChildren,
      ])
      ->orderBy('sort_order')
      ->orderBy('name')
      ->get();
  }

  #[Computed]
  public function workers(): Collection
  {
    return Employee::query()
      ->select(['id', 'user_id', 'full_name', 'phone', 'email', 'position', 'staff_position_id', 'status'])
      ->with([
        'user:id,name,email',
        'staffPosition:id,department_id,name',
        'staffPosition.department:id,name',
        'departments:id,name',
      ])
      ->when($this->search !== '', function ($query): void {
        $query->where(function ($searchQuery): void {
          $searchQuery
            ->where('full_name', 'like', '%' . $this->search . '%')
            ->orWhere('phone', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhere('position', 'like', '%' . $this->search . '%');
        });
      })
      ->orderBy('full_name')
      ->get();
  }

  #[Computed]
  public function departmentWorkers(): Collection
  {
    return Employee::query()
      ->select(['id', 'user_id', 'full_name', 'phone', 'email', 'position', 'staff_position_id', 'status'])
      ->with([
        'user:id,name,email',
        'staffPosition:id,department_id,name',
        'staffPosition.department:id,name',
      ])
      ->when($this->department_employee_search !== '', function ($query): void {
        $query->where(function ($searchQuery): void {
          $searchQuery
            ->where('full_name', 'like', '%' . $this->department_employee_search . '%')
            ->orWhere('phone', 'like', '%' . $this->department_employee_search . '%')
            ->orWhere('email', 'like', '%' . $this->department_employee_search . '%')
            ->orWhere('position', 'like', '%' . $this->department_employee_search . '%');
        });
      })
      ->orderBy('full_name')
      ->get();
  }

  #[Computed]
  public function editingDepartmentWorkers(): Collection
  {
    return Employee::query()
      ->select(['id', 'user_id', 'full_name', 'phone', 'email', 'position', 'staff_position_id', 'status'])
      ->with([
        'user:id,name,email',
        'staffPosition:id,department_id,name',
        'staffPosition.department:id,name',
        'departments:id,name',
      ])
      ->when($this->editing_department_employee_search !== '', function ($query): void {
        $query->where(function ($searchQuery): void {
          $searchQuery
            ->where('full_name', 'like', '%' . $this->editing_department_employee_search . '%')
            ->orWhere('phone', 'like', '%' . $this->editing_department_employee_search . '%')
            ->orWhere('email', 'like', '%' . $this->editing_department_employee_search . '%')
            ->orWhere('position', 'like', '%' . $this->editing_department_employee_search . '%');
        });
      })
      ->orderBy('full_name')
      ->get();
  }

  #[Computed]
  public function staffPositions(): Collection
  {
    return StaffPosition::query()
      ->select(['id', 'department_id', 'name', 'planned_count', 'is_active'])
      ->withCount('employees')
      ->with('department:id,name')
      ->orderBy('name')
      ->get();
  }

  public function createDepartment(CreateDepartment $createDepartment): void
  {
    $validated = $this->validate([
      'department_name' => ['required', 'string', 'max:255'],
      'department_sort_order' => ['required', 'integer', 'min:0'],
      'department_is_active' => ['required', 'boolean'],
      'department_parent_id' => ['nullable', 'integer', 'exists:organization_departments,id'],
      'department_employee_ids' => ['array'],
      'department_employee_ids.*' => ['integer', 'exists:employees,id'],
    ]);

    $createDepartment->handle([
      'name' => $validated['department_name'],
      'sort_order' => $validated['department_sort_order'],
      'is_active' => $validated['department_is_active'],
      'parent_id' => $validated['department_parent_id'] ?? null,
      'employee_ids' => array_map('intval', $validated['department_employee_ids'] ?? []),
    ]);

    $this->dispatch('close-modal', name: 'create-company-structure');
    $this->dispatch('close-modal', name: 'create-department');
    $this->reset(
      'department_name',
      'department_sort_order',
      'department_is_active',
      'department_parent_id',
      'department_employee_ids',
      'department_employee_search',
    );

    Flux::toast(variant: 'success', text: __('Department created.'));
  }

  public function editDepartment(int $departmentId): void
  {
    $department = Department::query()
      ->with('employees:id,full_name,email')
      ->findOrFail($departmentId);

    $this->editing_department_id = $department->id;
    $this->editing_department_name = $department->name;
    $this->editing_department_sort_order = (string) $department->sort_order;
    $this->editing_department_is_active = $department->is_active ? '1' : '0';
    $this->editing_department_parent_id = $department->parent_id;
    $this->editing_department_employee_ids = $department->employees->pluck('id')->map(
      fn($employeeId): int => (int) $employeeId,
    )->all();
    $this->editing_department_employee_search = '';

    $this->dispatch('modal-show', name: 'edit-department');
  }

  public function updateDepartment(): void
  {
    $validated = $this->validate([
      'editing_department_id' => ['required', 'integer', 'exists:organization_departments,id'],
      'editing_department_name' => ['required', 'string', 'max:255'],
      'editing_department_sort_order' => ['required', 'integer', 'min:0'],
      'editing_department_is_active' => ['required', 'boolean'],
      'editing_department_parent_id' => ['nullable', 'integer', 'exists:organization_departments,id'],
      'editing_department_employee_ids' => ['array'],
      'editing_department_employee_ids.*' => ['integer', 'exists:employees,id'],
    ]);

    $department = Department::query()->findOrFail($validated['editing_department_id']);

    $department->update([
      'name' => $validated['editing_department_name'],
      'sort_order' => $validated['editing_department_sort_order'],
      'is_active' => $validated['editing_department_is_active'],
      'parent_id' => $validated['editing_department_parent_id'] ?? null,
    ]);

    $department->employees()->sync(array_map('intval', $validated['editing_department_employee_ids'] ?? []));

    if (($validated['editing_department_employee_ids'] ?? []) !== []) {
      \Illuminate\Support\Facades\DB::table('department_employee')
        ->whereIn('employee_id', array_map('intval', $validated['editing_department_employee_ids'] ?? []))
        ->where('department_id', '!=', $department->id)
        ->delete();
    }

    $this->dispatch('close-modal', name: 'edit-department');

    $this->reset(
      'editing_department_id',
      'editing_department_name',
      'editing_department_sort_order',
      'editing_department_is_active',
      'editing_department_parent_id',
      'editing_department_employee_ids',
      'editing_department_employee_search',
    );

    Flux::toast(variant: 'success', text: __('Department updated.'));
  }

  public function deleteDepartment(): void
  {
    $department = Department::query()->findOrFail($this->editing_department_id);

    $department->delete();

    $this->dispatch('close-modal', name: 'edit-department');

    $this->reset(
      'editing_department_id',
      'editing_department_name',
      'editing_department_sort_order',
      'editing_department_is_active',
      'editing_department_parent_id',
      'editing_department_employee_ids',
      'editing_department_employee_search',
    );

    Flux::toast(variant: 'success', text: __('Department deleted.'));
  }

  public function createStaffPosition(CreateStaffPosition $createStaffPosition): void
  {
    $validated = $this->validate([
      'staff_position_department_id' => ['required', 'integer', 'exists:organization_departments,id'],
      'staff_position_name' => ['required', 'string', 'max:255'],
      'staff_position_planned_count' => ['required', 'integer', 'min:0'],
      'staff_position_sort_order' => ['required', 'integer', 'min:0'],
      'staff_position_is_active' => ['required', 'boolean'],
    ]);

    $createStaffPosition->handle([
      'department_id' => $validated['staff_position_department_id'],
      'name' => $validated['staff_position_name'],
      'planned_count' => $validated['staff_position_planned_count'],
      'sort_order' => $validated['staff_position_sort_order'],
      'is_active' => $validated['staff_position_is_active'],
    ]);

    $this->dispatch('close-modal', name: 'create-staff-position');
    $this->reset(
      'staff_position_department_id',
      'staff_position_name',
      'staff_position_planned_count',
      'staff_position_sort_order',
      'staff_position_is_active',
    );

    Flux::toast(variant: 'success', text: __('Position created.'));
  }

  public function createEmployee(CreateEmployee $createEmployee): void
  {
    $validated = $this->validate([
      'full_name' => ['required', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:255'],
      'email' => ['nullable', 'email', 'max:255'],
      'position' => ['nullable', 'string', 'max:255'],
      'employee_department_id' => ['required', 'integer', 'exists:organization_departments,id'],
    ]);

    $employee = $createEmployee->handle([
      'full_name' => $validated['full_name'],
      'phone' => $validated['phone'] ?? null,
      'email' => $validated['email'] ?? null,
      'position' => $validated['position'] ?? null,
      'status' => EmployeeStatus::Active,
    ]);

    $employee->departments()->sync([$validated['employee_department_id']]);

    $this->dispatch('close-modal', name: 'create-employee');
    $this->reset(
      'full_name',
      'phone',
      'email',
      'position',
      'employee_department_id',
    );

    Flux::toast(variant: 'success', text: __('Employee created.'));
  }

  #[Computed]
  public function editingEmployeeStaffPositions(): Collection
  {
    return StaffPosition::query()
      ->select(['id', 'department_id', 'name', 'planned_count', 'is_active'])
      ->with('department:id,name')
      ->when($this->editing_employee_department_id !== null, function ($query): void {
        $query->where('department_id', $this->editing_employee_department_id);
      })
      ->orderBy('name')
      ->get();
  }

  #[Computed]
  public function workspaceProjects(): Collection
  {
    $workspace = auth()->user()?->currentWorkspace;

    if ($workspace === null) {
      return collect();
    }

    return Project::query()
      ->select(['id', 'workspace_id', 'name'])
      ->where('workspace_id', $workspace->id)
      ->orderBy('name')
      ->get();
  }

  public function editEmployee(int $employeeId): void
  {
    $employee = Employee::query()
      ->with([
        'departments:id,name',
        'staffPosition:id,department_id,name',
        'staffPosition.department:id,name',
        'user:id,name,email',
      ])
      ->findOrFail($employeeId);

    $this->editing_employee_id = $employee->id;
    $this->editing_employee_full_name = $employee->full_name;
    $this->editing_employee_phone = $employee->phone ?? '';
    $this->editing_employee_email = $employee->email ?? '';
    $this->editing_employee_position = $employee->position ?? '';
    $this->editing_employee_department_id = $employee->departments->first()?->id
      ?? $employee->staffPosition?->department_id;
    $this->editing_employee_staff_position_id = $employee->staff_position_id;
    $this->editing_employee_project_ids = $employee->user_id === null
      ? []
      : Project::query()
        ->select(['id', 'workspace_id', 'name'])
        ->where('workspace_id', auth()->user()?->currentWorkspace?->id)
        ->whereHas('members', function ($query) use ($employee): void {
          $query->whereKey($employee->user_id);
        })
        ->pluck('id')
        ->map(fn($projectId): int => (int) $projectId)
        ->all();

    $this->dispatch('modal-show', name: 'edit-employee');
  }

  public function updateEmployee(): void
  {
    $validated = $this->validate([
      'editing_employee_id' => ['required', 'integer', 'exists:employees,id'],
      'editing_employee_full_name' => ['required', 'string', 'max:255'],
      'editing_employee_phone' => ['nullable', 'string', 'max:255'],
      'editing_employee_email' => ['nullable', 'email', 'max:255'],
      'editing_employee_position' => ['nullable', 'string', 'max:255'],
      'editing_employee_department_id' => ['required', 'integer', 'exists:organization_departments,id'],
      'editing_employee_staff_position_id' => ['nullable', 'integer', 'exists:organization_staff_positions,id'],
      'editing_employee_project_ids' => ['array'],
      'editing_employee_project_ids.*' => ['integer', 'exists:projects,id'],
    ]);

    $employee = Employee::query()->findOrFail($validated['editing_employee_id']);

    $employee->update([
      'full_name' => $validated['editing_employee_full_name'],
      'phone' => $validated['editing_employee_phone'] ?? null,
      'email' => $validated['editing_employee_email'] ?? null,
      'position' => $validated['editing_employee_position'] ?? null,
      'staff_position_id' => $validated['editing_employee_staff_position_id'] ?? null,
    ]);

    $employee->departments()->sync([$validated['editing_employee_department_id']]);

    if ($employee->user_id !== null) {
      $workspace = auth()->user()?->currentWorkspace;
      $selectedProjectIds = array_map('intval', $validated['editing_employee_project_ids'] ?? []);

      if ($workspace !== null) {
        $currentProjectIds = Project::query()
          ->select(['id'])
          ->where('workspace_id', $workspace->id)
          ->whereHas('members', function ($query) use ($employee): void {
            $query->whereKey($employee->user_id);
          })
          ->pluck('id')
          ->map(fn($projectId): int => (int) $projectId)
          ->all();

        Project::query()
          ->select(['id'])
          ->whereIn('id', $currentProjectIds)
          ->whereNotIn('id', $selectedProjectIds)
          ->get()
          ->each(function (Project $project) use ($employee): void {
            $project->members()->detach($employee->user_id);
          });

        Project::query()
          ->select(['id', 'workspace_id'])
          ->whereIn('id', $selectedProjectIds)
          ->where('workspace_id', $workspace->id)
          ->get()
          ->each(function (Project $project) use ($employee, $workspace): void {
            $project->members()->syncWithoutDetaching([
              $employee->user_id => [
                'workspace_id' => $workspace->id,
                'role' => 'member',
              ],
            ]);
          });
      }
    }

    $this->dispatch('close-modal', name: 'edit-employee');
    $this->reset(
      'editing_employee_id',
      'editing_employee_full_name',
      'editing_employee_phone',
      'editing_employee_email',
      'editing_employee_position',
      'editing_employee_department_id',
      'editing_employee_staff_position_id',
      'editing_employee_project_ids',
    );

    Flux::toast(variant: 'success', text: __('Employee updated.'));
  }
}; ?>

<section class="w-full">
  @include('partials.settings-heading')

  <flux:heading class="sr-only">{{ __('Workers') }}</flux:heading>

  <x-pages::settings.layout :heading="__('Workers')" :subheading="__('Manage company employees and system access')"
    fullWidth>
@include('components.settings.workers.departments-section')

@include('components.settings.workers.staff-section')

@include('components.settings.workers.modals.create-employee')

@include('components.settings.workers.modals.create-company-structure')

@include('components.settings.workers.modals.create-department')

@include('components.settings.workers.modals.edit-department')

@include('components.settings.workers.modals.create-staff-position')

@include('components.settings.workers.modals.edit-employee')
  </x-pages::settings.layout>
</section>
