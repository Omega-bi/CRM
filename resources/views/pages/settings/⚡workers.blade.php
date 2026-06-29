<?php

use App\Enums\ProjectRole;
use Modules\Workspace\Enums\WorkspaceRole;
use App\Models\Project;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Str;
use Modules\Employee\Actions\CreateEmployee;
use Modules\Employee\Actions\GrantEmployeeSystemAccess;
use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;
use Modules\Organization\Actions\CreateDepartment;
use Modules\Organization\Actions\CreateStaffPosition;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\StaffPosition;

new #[Title('Workers settings')] class extends Component {
  public string $search = '';
  public int $workers_per_page = 10;
  public int $workers_page = 1;

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
  public string $staff_position_is_active = '1';
  public ?int $staff_position_department_id = null;
  public string $staff_position_search = '';
  public int $staff_positions_per_page = 10;
  public int $staff_positions_page = 1;
  public ?int $editing_staff_position_id = null;
  public string $editing_staff_position_name = '';
  public string $editing_staff_position_is_active = '1';
  public ?int $editing_staff_position_department_id = null;

  public string $full_name = '';
  public string $phone = '';
  public string $email = '';
  public string $position = '';
  public ?int $employee_department_id = null;
  public ?int $employee_staff_position_id = null;

  public ?int $editing_employee_id = null;
  public string $editing_employee_full_name = '';
  public string $editing_employee_phone = '';
  public string $editing_employee_email = '';
  public string $editing_employee_position = '';
  public ?int $editing_employee_department_id = null;
  public array $editing_employee_department_ids = [];
  public int $editing_employee_departments_per_page = 10;
  public int $editing_employee_departments_page = 1;
  public ?int $editing_employee_staff_position_id = null;
  public array $editing_employee_project_ids = [];
  public int $editing_employee_projects_per_page = 10;
  public int $editing_employee_projects_page = 1;
  public ?int $editing_employee_user_id = null;
  public ?int $account_employee_id = null;
  public string $account_name = '';
  public string $account_email = '';
  public string $account_password = '';
  public string $account_password_confirmation = '';

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
    return $this->workersQuery()
      ->select(['id', 'user_id', 'full_name', 'phone', 'email', 'position', 'staff_position_id', 'status'])
      ->with([
        'user:id,name,email',
        'staffPosition:id,department_id,name',
        'staffPosition.department:id,name',
        'departments:id,name',
      ])
      ->orderBy('full_name')
      ->offset(($this->workers_page - 1) * $this->workers_per_page)
      ->limit($this->workers_per_page)
      ->get();
  }

  #[Computed]
  public function workersTotal(): int
  {
    return $this->workersQuery()->count();
  }

  #[Computed]
  public function departmentWorkers(): Collection
  {
    return $this->departmentWorkersQuery()
      ->select(['id', 'user_id', 'full_name', 'phone', 'email', 'position', 'staff_position_id', 'status'])
      ->with([
        'user:id,name,email',
        'staffPosition:id,department_id,name',
        'staffPosition.department:id,name',
      ])
      ->orderBy('full_name')
      ->get();
  }

  #[Computed]
  public function editingDepartmentWorkers(): Collection
  {
    return $this->editingDepartmentWorkersQuery()
      ->select(['id', 'user_id', 'full_name', 'phone', 'email', 'position', 'staff_position_id', 'status'])
      ->with([
        'user:id,name,email',
        'staffPosition:id,department_id,name',
        'staffPosition.department:id,name',
        'departments:id,name',
      ])
      ->orderBy('full_name')
      ->get();
  }

  #[Computed]
  public function editingEmployeeDepartments(): Collection
  {
    return Department::query()
      ->select(['id', 'name'])
      ->orderBy('sort_order')
      ->orderBy('name')
      ->offset(($this->editing_employee_departments_page - 1) * $this->editing_employee_departments_per_page)
      ->limit($this->editing_employee_departments_per_page)
      ->get();
  }

  #[Computed]
  public function editingEmployeeDepartmentsTotal(): int
  {
    return Department::query()->count();
  }

  #[Computed]
  public function staffPositions(): Collection
  {
    return $this->staffPositionsQuery()
      ->select(['id', 'department_id', 'name', 'planned_count', 'is_active'])
      ->withCount('employees')
      ->with('department:id,name')
      ->orderBy('name')
      ->offset(($this->staff_positions_page - 1) * $this->staff_positions_per_page)
      ->limit($this->staff_positions_per_page)
      ->get();
  }

  #[Computed]
  public function staffPositionsTotal(): int
  {
    return $this->staffPositionsQuery()->count();
  }

  public function updatedSearch(): void
  {
    $this->workers_page = 1;
    unset($this->workers, $this->workersTotal);
  }

  public function updatedDepartmentEmployeeSearch(): void
  {
    unset($this->departmentWorkers);
  }

  public function updatedEditingDepartmentEmployeeSearch(): void
  {
    unset($this->editingDepartmentWorkers);
  }

  public function updatedStaffPositionSearch(): void
  {
    $this->staff_positions_page = 1;
    unset($this->staffPositions, $this->staffPositionsTotal);
  }

  public function setWorkersPage(int $page): void
  {
    $this->workers_page = $this->clampedPage($page, $this->workersTotal(), $this->workers_per_page);
    unset($this->workers);
  }

  public function previousWorkersPage(): void
  {
    $this->setWorkersPage($this->workers_page - 1);
  }

  public function nextWorkersPage(): void
  {
    $this->setWorkersPage($this->workers_page + 1);
  }

  public function setStaffPositionsPage(int $page): void
  {
    $this->staff_positions_page = $this->clampedPage($page, $this->staffPositionsTotal(), $this->staff_positions_per_page);
    unset($this->staffPositions);
  }

  public function previousStaffPositionsPage(): void
  {
    $this->setStaffPositionsPage($this->staff_positions_page - 1);
  }

  public function nextStaffPositionsPage(): void
  {
    $this->setStaffPositionsPage($this->staff_positions_page + 1);
  }

  public function setEditingEmployeeDepartmentsPage(int $page): void
  {
    $this->editing_employee_departments_page = $this->clampedPage($page, $this->editingEmployeeDepartmentsTotal(), $this->editing_employee_departments_per_page);
    unset($this->editingEmployeeDepartments);
  }

  public function previousEditingEmployeeDepartmentsPage(): void
  {
    $this->setEditingEmployeeDepartmentsPage($this->editing_employee_departments_page - 1);
  }

  public function nextEditingEmployeeDepartmentsPage(): void
  {
    $this->setEditingEmployeeDepartmentsPage($this->editing_employee_departments_page + 1);
  }

  public function setEditingEmployeeProjectsPage(int $page): void
  {
    $this->editing_employee_projects_page = $this->clampedPage($page, $this->workspaceProjectsTotal(), $this->editing_employee_projects_per_page);
    unset($this->workspaceProjects);
  }

  public function previousEditingEmployeeProjectsPage(): void
  {
    $this->setEditingEmployeeProjectsPage($this->editing_employee_projects_page - 1);
  }

  public function nextEditingEmployeeProjectsPage(): void
  {
    $this->setEditingEmployeeProjectsPage($this->editing_employee_projects_page + 1);
  }

  private function clampedPage(int $page, int $total, int $perPage): int
  {
    $lastPage = max(1, (int) ceil($total / $perPage));

    return min(max(1, $page), $lastPage);
  }

  public function showAllDepartments(): void
  {
    $this->show_all_departments = true;
    unset($this->departments);
  }

  private function workersQuery(): Builder
  {
    return Employee::query()
      ->when($this->search !== '', function ($query): void {
        $query->where(function ($searchQuery): void {
          $searchQuery
            ->where('full_name', 'like', '%' . $this->search . '%')
            ->orWhere('phone', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhere('position', 'like', '%' . $this->search . '%');
        });
      });
  }

  private function staffPositionsQuery(): Builder
  {
    return StaffPosition::query()
      ->when($this->staff_position_search !== '', function ($query): void {
        $search = '%' . $this->staff_position_search . '%';

        $query->where(function ($searchQuery) use ($search): void {
          $searchQuery->where('name', 'like', $search);

          $searchQuery->orWhereHas('department', function ($departmentQuery) use ($search): void {
            $departmentQuery->where('name', 'like', $search);
          });
        });
      });
  }

  private function departmentWorkersQuery(): Builder
  {
    return Employee::query()
      ->when($this->department_employee_search !== '', function ($query): void {
        $query->where(function ($searchQuery): void {
          $searchQuery
            ->where('full_name', 'like', '%' . $this->department_employee_search . '%')
            ->orWhere('phone', 'like', '%' . $this->department_employee_search . '%')
            ->orWhere('email', 'like', '%' . $this->department_employee_search . '%')
            ->orWhere('position', 'like', '%' . $this->department_employee_search . '%');
        });
      });
  }

  private function editingDepartmentWorkersQuery(): Builder
  {
    return Employee::query()
      ->when($this->editing_department_employee_search !== '', function ($query): void {
        $query->where(function ($searchQuery): void {
          $searchQuery
            ->where('full_name', 'like', '%' . $this->editing_department_employee_search . '%')
            ->orWhere('phone', 'like', '%' . $this->editing_department_employee_search . '%')
            ->orWhere('email', 'like', '%' . $this->editing_department_employee_search . '%')
            ->orWhere('position', 'like', '%' . $this->editing_department_employee_search . '%');
        });
      });
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

  public function moveDepartment(int $departmentId, int $parentDepartmentId): void
  {
    if ($departmentId === $parentDepartmentId) {
      return;
    }

    $department = Department::query()->findOrFail($departmentId);
    $parentDepartment = Department::query()->findOrFail($parentDepartmentId);

    if ($this->departmentBelongsToBranch($parentDepartment->id, $department->id)) {
      Flux::toast(variant: 'warning', text: __('Department cannot be moved inside its own child department.'));

      return;
    }

    $nextSortOrder = (int) Department::query()
      ->where('parent_id', $parentDepartment->id)
      ->max('sort_order');

    $department->update([
      'parent_id' => $parentDepartment->id,
      'sort_order' => $nextSortOrder + 1,
    ]);

    Flux::toast(variant: 'success', text: __('Department moved.'));
  }

  private function departmentBelongsToBranch(int $departmentId, int $branchRootId): bool
  {
    $currentDepartment = Department::query()
      ->select(['id', 'parent_id'])
      ->find($departmentId);

    while ($currentDepartment !== null) {
      if ($currentDepartment->id === $branchRootId) {
        return true;
      }

      if ($currentDepartment->parent_id === null) {
        return false;
      }

      $currentDepartment = Department::query()
        ->select(['id', 'parent_id'])
        ->find($currentDepartment->parent_id);
    }

    return false;
  }

  public function createStaffPosition(CreateStaffPosition $createStaffPosition): void
  {
    $validated = $this->validate([
      'staff_position_department_id' => ['required', 'integer', 'exists:organization_departments,id'],
      'staff_position_name' => ['required', 'string', 'max:255'],
      'staff_position_is_active' => ['required', 'boolean'],
    ]);

    $nextSortOrder = (int) StaffPosition::query()
      ->where('department_id', $validated['staff_position_department_id'])
      ->max('sort_order');

    $createStaffPosition->handle([
      'department_id' => $validated['staff_position_department_id'],
      'name' => $validated['staff_position_name'],
      'planned_count' => 0,
      'sort_order' => $nextSortOrder + 1,
      'is_active' => $validated['staff_position_is_active'],
    ]);

    $this->dispatch('close-modal', name: 'create-staff-position');
    $this->reset(
      'staff_position_department_id',
      'staff_position_name',
      'staff_position_is_active',
    );

    Flux::toast(variant: 'success', text: __('Position created.'));
  }

  public function editStaffPosition(int $positionId): void
  {
    $staffPosition = StaffPosition::query()
      ->findOrFail($positionId);

    $this->editing_staff_position_id = $staffPosition->id;
    $this->editing_staff_position_name = $staffPosition->name;
    $this->editing_staff_position_department_id = $staffPosition->department_id;
    $this->editing_staff_position_is_active = $staffPosition->is_active ? '1' : '0';

    $this->dispatch('modal-show', name: 'edit-staff-position');
  }

  public function updateStaffPosition(): void
  {
    $validated = $this->validate([
      'editing_staff_position_id' => ['required', 'integer', 'exists:organization_staff_positions,id'],
      'editing_staff_position_department_id' => ['required', 'integer', 'exists:organization_departments,id'],
      'editing_staff_position_name' => ['required', 'string', 'max:255'],
      'editing_staff_position_is_active' => ['required', 'boolean'],
    ]);

    $staffPosition = StaffPosition::query()->findOrFail($validated['editing_staff_position_id']);

    $staffPosition->update([
      'department_id' => $validated['editing_staff_position_department_id'],
      'name' => $validated['editing_staff_position_name'],
      'is_active' => $validated['editing_staff_position_is_active'],
    ]);

    $this->dispatch('close-modal', name: 'edit-staff-position');
    $this->reset(
      'editing_staff_position_id',
      'editing_staff_position_name',
      'editing_staff_position_department_id',
      'editing_staff_position_is_active',
    );

    Flux::toast(variant: 'success', text: __('Position updated.'));
  }

  public function createEmployee(CreateEmployee $createEmployee): void
  {
    $validated = $this->validate([
      'full_name' => ['required', 'string', 'max:255'],
      'phone' => ['nullable', 'string', 'max:255'],
      'email' => ['nullable', 'email', 'max:255'],
      'position' => ['nullable', 'string', 'max:255'],
      'employee_department_id' => ['required', 'integer', 'exists:organization_departments,id'],
      'employee_staff_position_id' => ['nullable', 'integer', 'exists:organization_staff_positions,id'],
    ]);

    $staffPosition = null;

    if (($validated['employee_staff_position_id'] ?? null) !== null) {
      $staffPosition = StaffPosition::query()
        ->select(['id', 'department_id', 'name'])
        ->findOrFail($validated['employee_staff_position_id']);

      $validated['employee_department_id'] = $staffPosition->department_id;
    }

    $employee = $createEmployee->handle([
      'full_name' => $validated['full_name'],
      'phone' => $validated['phone'] ?? null,
      'email' => $validated['email'] ?? null,
      'position' => $staffPosition?->name ?? ($validated['position'] ?? null),
      'staff_position_id' => $staffPosition?->id,
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
      'employee_staff_position_id',
    );

    Flux::toast(variant: 'success', text: __('Employee created.'));
  }

  #[Computed]
  public function employeeStaffPositions(): Collection
  {
    if ($this->employee_department_id === null) {
      return collect();
    }

    return StaffPosition::query()
      ->select(['id', 'department_id', 'name', 'is_active'])
      ->with('department:id,name')
      ->where('is_active', true)
      ->where('department_id', $this->employee_department_id)
      ->orderBy('name')
      ->get();
  }

  public function updatedEmployeeDepartmentId(): void
  {
    if ($this->employee_staff_position_id === null) {
      return;
    }

    $staffPositionBelongsToDepartment = StaffPosition::query()
      ->whereKey($this->employee_staff_position_id)
      ->where('department_id', $this->employee_department_id)
      ->exists();

    if (!$staffPositionBelongsToDepartment) {
      $this->employee_staff_position_id = null;
      $this->position = '';
    }
  }

  public function updatedEmployeeStaffPositionId(): void
  {
    if ($this->employee_staff_position_id === null) {
      $this->position = '';

      return;
    }

    $staffPosition = StaffPosition::query()
      ->select(['id', 'department_id', 'name'])
      ->find($this->employee_staff_position_id);

    if ($staffPosition === null) {
      $this->employee_staff_position_id = null;
      $this->position = '';

      return;
    }

    $this->employee_department_id = $staffPosition->department_id;
    $this->position = $staffPosition->name;
  }

  #[Computed]
  public function editingEmployeeStaffPositions(): Collection
  {
    $departmentIds = $this->editing_employee_department_ids !== []
      ? array_map('intval', $this->editing_employee_department_ids)
      : [];

    return StaffPosition::query()
      ->select(['id', 'department_id', 'name', 'planned_count', 'is_active'])
      ->with('department:id,name')
      ->when($departmentIds !== [], function ($query) use ($departmentIds): void {
        $query->whereIn('department_id', $departmentIds);
      })
      ->when($departmentIds === [] && $this->editing_employee_department_id !== null, function ($query): void {
        $query->where('department_id', $this->editing_employee_department_id);
      })
      ->orderBy('name')
      ->get();
  }

  #[Computed]
  public function editingEmployeePositionLabel(): string
  {
    if ($this->editing_employee_staff_position_id === null) {
      return $this->editing_employee_position !== ''
        ? $this->editing_employee_position
        : __('Not set');
    }

    return StaffPosition::query()
      ->whereKey($this->editing_employee_staff_position_id)
      ->value('name') ?? __('Not set');
  }

  #[Computed]
  public function workspaceProjects(): Collection
  {
    return $this->workspaceProjectsQuery()
      ->select(['id', 'workspace_id', 'name'])
      ->orderBy('name')
      ->offset(($this->editing_employee_projects_page - 1) * $this->editing_employee_projects_per_page)
      ->limit($this->editing_employee_projects_per_page)
      ->get();
  }

  #[Computed]
  public function workspaceProjectsTotal(): int
  {
    return $this->workspaceProjectsQuery()->count();
  }

  private function workspaceProjectsQuery(): Builder
  {
    $workspace = auth()->user()?->currentWorkspace;

    if ($workspace === null) {
      return Project::query()->whereRaw('1 = 0');
    }

    return Project::query()
      ->where('workspace_id', $workspace->id);
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
    $this->editing_employee_department_ids = $employee->departments
      ->pluck('id')
      ->map(fn($departmentId): int => (int) $departmentId)
      ->all();

    if ($this->editing_employee_department_ids === [] && $this->editing_employee_department_id !== null) {
      $this->editing_employee_department_ids = [$this->editing_employee_department_id];
    }

    $this->editing_employee_staff_position_id = $employee->staff_position_id;
    $this->editing_employee_user_id = $employee->user_id;
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
    $this->editing_employee_departments_page = 1;
    $this->editing_employee_projects_page = 1;
    unset($this->editingEmployeeDepartments, $this->workspaceProjects);

    $this->dispatch('modal-show', name: 'edit-employee');
  }

  public function updatedEditingEmployeeDepartmentIds(): void
  {
    $departmentIds = array_values(array_unique(array_map('intval', $this->editing_employee_department_ids)));

    $this->editing_employee_department_ids = $departmentIds;

    if ($departmentIds === []) {
      $this->editing_employee_department_id = null;
      $this->editing_employee_staff_position_id = null;

      return;
    }

    if (
      $this->editing_employee_department_id === null
      || !in_array($this->editing_employee_department_id, $departmentIds, true)
    ) {
      $this->editing_employee_department_id = $departmentIds[0];
      $this->editing_employee_staff_position_id = null;
    }

    if ($this->editing_employee_staff_position_id !== null) {
      $staffPositionBelongsToSelectedDepartment = StaffPosition::query()
        ->whereKey($this->editing_employee_staff_position_id)
        ->whereIn('department_id', $departmentIds)
        ->exists();

      if (!$staffPositionBelongsToSelectedDepartment) {
        $this->editing_employee_staff_position_id = null;
      }
    }
  }

  public function updatedEditingEmployeeStaffPositionId(): void
  {
    if ($this->editing_employee_staff_position_id === null) {
      return;
    }

    $staffPosition = StaffPosition::query()
      ->select(['id', 'department_id', 'name'])
      ->find($this->editing_employee_staff_position_id);

    if ($staffPosition === null) {
      $this->editing_employee_staff_position_id = null;

      return;
    }

    $this->editing_employee_department_id = $staffPosition->department_id;

    if (!in_array($staffPosition->department_id, $this->editing_employee_department_ids, true)) {
      $this->editing_employee_department_ids[] = $staffPosition->department_id;
    }

    $this->editing_employee_position = $staffPosition->name;
  }

  public function updateEmployee(): void
  {
    $validated = $this->validate([
      'editing_employee_id' => ['required', 'integer', 'exists:employees,id'],
      'editing_employee_full_name' => ['required', 'string', 'max:255'],
      'editing_employee_phone' => ['nullable', 'string', 'max:255'],
      'editing_employee_email' => ['nullable', 'email', 'max:255'],
      'editing_employee_position' => ['nullable', 'string', 'max:255'],
      'editing_employee_department_id' => ['nullable', 'integer', 'exists:organization_departments,id'],
      'editing_employee_department_ids' => ['array', 'min:1'],
      'editing_employee_department_ids.*' => ['integer', 'exists:organization_departments,id'],
      'editing_employee_staff_position_id' => ['nullable', 'integer', 'exists:organization_staff_positions,id'],
      'editing_employee_project_ids' => ['array'],
      'editing_employee_project_ids.*' => ['integer', 'exists:projects,id'],
    ]);

    $employee = Employee::query()->findOrFail($validated['editing_employee_id']);
    $staffPositionName = null;

    if (($validated['editing_employee_staff_position_id'] ?? null) !== null) {
      $staffPositionName = StaffPosition::query()
        ->whereKey($validated['editing_employee_staff_position_id'])
        ->value('name');
    }

    $employee->update([
      'full_name' => $validated['editing_employee_full_name'],
      'phone' => $validated['editing_employee_phone'] ?? null,
      'email' => $validated['editing_employee_email'] ?? null,
      'position' => $staffPositionName ?? ($validated['editing_employee_position'] ?? null),
      'staff_position_id' => $validated['editing_employee_staff_position_id'] ?? null,
    ]);

    $departmentIds = array_map('intval', $validated['editing_employee_department_ids'] ?? []);

    if ($departmentIds === [] && isset($validated['editing_employee_department_id'])) {
      $departmentIds = [(int) $validated['editing_employee_department_id']];
    }

    $employee->departments()->sync($departmentIds);

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
                'role' => ProjectRole::Observer->value,
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
      'editing_employee_department_ids',
      'editing_employee_staff_position_id',
      'editing_employee_project_ids',
      'editing_employee_user_id',
    );

    Flux::toast(variant: 'success', text: __('Employee updated.'));
  }

  public function openCreateEmployeeAccountModal(): void
  {
    $employee = Employee::query()->findOrFail($this->editing_employee_id);

    if ($employee->user_id !== null) {
      Flux::toast(variant: 'warning', text: __('Employee already has an account.'));

      return;
    }

    $this->account_employee_id = $employee->id;
    $this->account_name = $this->editing_employee_full_name !== ''
      ? $this->editing_employee_full_name
      : $employee->full_name;
    $this->account_email = $this->editing_employee_email !== ''
      ? $this->editing_employee_email
      : ($employee->email ?? '');
    $this->account_password = '';
    $this->account_password_confirmation = '';

    $this->dispatch('close-modal', name: 'edit-employee');
    $this->dispatch('modal-show', name: 'create-employee-account');
  }

  public function createEmployeeAccount(GrantEmployeeSystemAccess $grantEmployeeSystemAccess): void
  {
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
    $workspace = auth()->user()?->currentWorkspace;

    if ($workspace !== null) {
      $workspace->members()->syncWithoutDetaching([
        $user->id => [
          'role' => WorkspaceRole::Member->value,
          'position' => $employee->position,
        ],
      ]);

      $user->switchWorkspace($workspace);
    }

    $this->editing_employee_user_id = $user->id;
    $this->dispatch('close-modal', name: 'create-employee-account');
    $this->reset(
      'account_employee_id',
      'account_name',
      'account_email',
      'account_password',
      'account_password_confirmation',
    );

    Flux::toast(variant: 'success', text: __('Employee account created.'));
  }

  public function deleteEmployee(): void
  {
    $validated = $this->validate([
      'editing_employee_id' => ['required', 'integer', 'exists:employees,id'],
    ]);

    $employee = Employee::query()->findOrFail($validated['editing_employee_id']);
    $workspace = auth()->user()?->currentWorkspace;

    DB::transaction(function () use ($employee, $workspace): void {
      $employee->departments()->detach();

      if ($employee->user_id !== null && $workspace !== null) {
        DB::table('project_members')
          ->where('workspace_id', $workspace->id)
          ->where('user_id', $employee->user_id)
          ->delete();
      }

      $employee->delete();
    });

    $this->dispatch('close-modal', name: 'edit-employee');
    $this->reset(
      'editing_employee_id',
      'editing_employee_full_name',
      'editing_employee_phone',
      'editing_employee_email',
      'editing_employee_position',
      'editing_employee_department_id',
      'editing_employee_department_ids',
      'editing_employee_staff_position_id',
      'editing_employee_project_ids',
      'editing_employee_user_id',
    );

    Flux::toast(variant: 'success', text: __('Employee deleted.'));
  }
}; ?>

<section class="w-full">
  <x-pages::settings.layout fullWidth>
    @include('components.settings.workers.departments-section')

    @include('components.settings.workers.staff-positions-section')

    @include('components.settings.workers.staff-section')

    @include('components.settings.workers.modals.create-employee')

    @include('components.settings.workers.modals.create-company-structure')

    @include('components.settings.workers.modals.create-department')

    @include('components.settings.workers.modals.edit-department')

    @include('components.settings.workers.modals.create-staff-position')

    @include('components.settings.workers.modals.edit-staff-position')

    @include('components.settings.workers.modals.edit-employee')

    @include('components.settings.workers.modals.create-employee-account')
  </x-pages::settings.layout>
</section>
