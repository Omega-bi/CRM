<?php

namespace Modules\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Organization\Models\Department;

class CreateDepartment
{
    /**
     * @param  array{name: string, sort_order?: int|null, is_active?: bool|null, parent_id?: int|null, employee_ids?: array<int, int>|null}  $data
     */
    public function handle(array $data): Department
    {
        $department = Department::create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $employeeIds = $data['employee_ids'] ?? [];

        if ($employeeIds !== []) {
            $department->employees()->sync($employeeIds);

            DB::table('department_employee')
                ->whereIn('employee_id', $employeeIds)
                ->where('department_id', '!=', $department->id)
                ->delete();
        }

        return $department;
    }
}
