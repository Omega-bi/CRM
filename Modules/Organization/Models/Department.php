<?php

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Employee\Models\Employee;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StaffPosition> $positions
 * @property-read Department|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Department> $children
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Employee> $employees
 */
#[Fillable(['name', 'sort_order', 'is_active', 'parent_id'])]
class Department extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organization_departments';

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_active' => true,
        'parent_id' => null,
    ];

    /**
     * Get the parent department.
     *
     * @return BelongsTo<Department, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Get the staff positions that belong to the department.
     *
     * @return HasMany<StaffPosition, $this>
     */
    public function positions(): HasMany
    {
        return $this->hasMany(StaffPosition::class);
    }

    /**
     * Get the child departments.
     *
     * @return HasMany<Department, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Get the employees assigned to this department.
     *
     * @return BelongsToMany<Employee, $this>
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'department_employee')
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'parent_id' => 'integer',
        ];
    }
}
