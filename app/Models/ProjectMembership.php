<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Modules\Access\Models\Role;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $project_id
 * @property int $user_id
 * @property int|null $access_role_id
 * @property ProjectRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read Project $project
 * @property-read User $user
 * @property-read Role|null $accessRole
 */
#[Fillable(['workspace_id', 'project_id', 'user_id', 'role', 'access_role_id'])]
class ProjectMembership extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_members';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the workspace for this project membership.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the project for this membership.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned through this membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dynamic access role assigned in this project.
     *
     * @return BelongsTo<Role, $this>
     */
    public function accessRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'access_role_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => ProjectRole::class,
        ];
    }
}
