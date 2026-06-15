<?php

namespace Modules\Organization\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property int $planned_count
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Department $department
 */
#[Fillable(['department_id', 'name', 'planned_count', 'sort_order', 'is_active'])]
class StaffPosition extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organization_staff_positions';

    /**
     * The attributes that should have default values.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'planned_count' => 0,
        'sort_order' => 0,
        'is_active' => true,
    ];

    /**
     * Get the department that owns this position.
     *
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the employees that belong to this position.
     *
     * @return HasMany<\Modules\Employee\Models\Employee, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(\Modules\Employee\Models\Employee::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'planned_count' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
