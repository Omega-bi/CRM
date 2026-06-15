<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Organization\Models\Department;
use Modules\Organization\Models\StaffPosition;
use Modules\Employee\Enums\EmployeeStatus;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $full_name
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $position
 * @property int|null $staff_position_id
 * @property EmployeeStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read StaffPosition|null $staffPosition
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $departments
 */
#[Fillable(['user_id', 'full_name', 'phone', 'email', 'position', 'staff_position_id', 'status'])]
class Employee extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => EmployeeStatus::Active->value,
    ];

    /**
     * Get the user account linked to this employee, if access was granted.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the staff position linked to this employee.
     *
     * @return BelongsTo<StaffPosition, $this>
     */
    public function staffPosition(): BelongsTo
    {
        return $this->belongsTo(StaffPosition::class);
    }

    /**
     * Get the departments this employee belongs to.
     *
     * @return BelongsToMany<Department, $this>
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_employee')
            ->withTimestamps();
    }

    /**
     * Determine whether this employee has a login account.
     */
    public function hasSystemAccess(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmployeeStatus::class,
        ];
    }
}
