<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Customer\Models\Customer;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $name
 * @property string|null $description
 * @property ProjectStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read Collection<int, ProjectMembership> $memberships
 * @property-read Collection<int, User> $members
 * @property-read Collection<int, Customer> $customers
 */
#[Fillable(['workspace_id', 'name', 'description', 'status', 'starts_at', 'ends_at'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ProjectStatus::Planned->value,
    ];

    /**
     * Get the workspace that owns this project.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get all project memberships.
     *
     * @return HasMany<ProjectMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    /**
     * Get all users assigned to this project.
     *
     * @return BelongsToMany<User, $this, ProjectMembership, 'pivot'>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members', 'project_id', 'user_id')
            ->using(ProjectMembership::class)
            ->withPivot(['workspace_id', 'role', 'access_role_id'])
            ->withTimestamps();
    }

    /**
     * Get customers connected to this project.
     *
     * @return BelongsToMany<Customer, $this>
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'project_customers')
            ->withPivot(['workspace_id', 'role'])
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
            'status' => ProjectStatus::class,
            'starts_at' => 'date',
            'ends_at' => 'date',
        ];
    }
}
