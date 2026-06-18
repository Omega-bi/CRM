<?php

namespace Modules\Workspace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Modules\Access\Models\Role;
use Modules\Workspace\Enums\WorkspaceRole;

/**
 * @property int $id
 * @property int $workspace_id
 * @property int $user_id
 * @property int|null $access_role_id
 * @property WorkspaceRole $role
 * @property string|null $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read User $user
 * @property-read Role|null $accessRole
 */
#[Fillable(['workspace_id', 'user_id', 'role', 'position', 'access_role_id'])]
class WorkspaceMember extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspace_members';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the workspace that the membership belongs to.
     *
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user that belongs to this membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the dynamic access role assigned in this workspace.
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
            'role' => WorkspaceRole::class,
        ];
    }
}
