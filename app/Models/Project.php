<?php

namespace App\Models;

use App\Database\Factories\ProjectFactory;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Workspace\Models\Workspace;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property ProjectStatus $status
 * @property int $workspace_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, ProjectMember> $members
 * @property-read Workspace $workspace
 */
#[Fillable(['name', 'slug', 'status', 'workspace_id'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    /**
     * Get the workspace that owns the project.
     */
    public function workspace(): BelongsToMany
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the members of the project.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ProjectMember::class);
    }
}
