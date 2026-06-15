<?php

namespace Modules\Access\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Modules\Access\Enums\RoleScope;
use Modules\Workspace\Models\Workspace;
use App\Models\Project;

/**
 * @property int $id
 * @property int|null $project_id
 * @property int|null $workspace_id
 * @property RoleScope $scope
 * @property string $name
 * @property string $code
 * @property bool $is_system
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace|null $workspace
 * @property-read Project|null $project
 * @property-read Collection<int, Permission> $permissions
 */
#[Fillable(['project_id', 'workspace_id', 'scope', 'name', 'code', 'is_system', 'description'])]
class Role extends Model
{
    /**
     * Get the workspace that owns this role.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the project that owns this role.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get permissions assigned to this role.
     *
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
            ->withTimestamps();
    }

    /**
     * Determine whether the role grants the given permission code.
     */
    public function allows(string $permissionCode): bool
    {
        if (! $this->relationLoaded('permissions')) {
            $this->load('permissions');
        }

        return $this->permissions->contains('code', $permissionCode);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => RoleScope::class,
            'is_system' => 'boolean',
        ];
    }
}
