<?php

namespace Modules\Employee\Actions;

use Modules\Employee\Enums\EmployeeStatus;
use Modules\Employee\Models\Employee;

class CreateEmployee
{
    /**
     * @param  array{full_name: string, phone?: string|null, email?: string|null, position?: string|null, staff_position_id?: int|null, status?: EmployeeStatus|string|null}  $data
     */
    public function handle(array $data): Employee
    {
        return Employee::create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'position' => $data['position'] ?? null,
            'staff_position_id' => $data['staff_position_id'] ?? null,
            'status' => $data['status'] ?? EmployeeStatus::Active,
        ]);
    }
}
