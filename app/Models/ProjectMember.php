<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property ProjectRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Project $project
 */
#[Fillable(['project_id', 'user_id', 'role'])]
class ProjectMember extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_members';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => ProjectRole::class,
    ];

    /**
     * Get the project that the member belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that is the member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
