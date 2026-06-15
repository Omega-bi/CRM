<?php

namespace Modules\Employee\Actions;

use App\Models\User;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Employee\Models\Employee;

class GrantEmployeeSystemAccess
{
    /**
     * Create or attach a login account for an employee.
     */
    public function handle(Employee $employee, ?User $user = null, ?string $password = null): User
    {
        return DB::transaction(function () use ($employee, $user, $password): User {
            if ($employee->user_id !== null && $user === null) {
                return $employee->user()->firstOrFail();
            }

            if ($user === null && $employee->email === null) {
                throw new InvalidArgumentException('Employee email is required to create a user account.');
            }

            $user ??= User::create([
                'name' => $employee->full_name,
                'email' => $employee->email,
                'password' => $password ?? Str::password(32),
            ]);

            $employee->forceFill(['user_id' => $user->id])->save();

            return $user;
        });
    }
}
