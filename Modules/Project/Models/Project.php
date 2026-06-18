<?php

namespace Modules\Project\Models;

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
use Modules\Workspace\Models\Workspace;

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
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the memberships for this project.
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(ProjectMembership::class);
    }

    /**
     * Get the members of this project.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_memberships', 'project_id', 'user_id');
    }

    /**
     * Get the customers associated with this project.
     */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'project_customers', 'project_id', 'customer_id');
    }
}
