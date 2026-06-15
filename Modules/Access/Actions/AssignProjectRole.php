<?php

namespace Modules\Access\Actions;

use App\Models\Project;
use App\Models\User;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Models\Role;

class AssignProjectRole
{
    public function handle(Project $project, User $user, Role $role): void
    {
        abort_unless($role->scope === RoleScope::Project, 422);
        abort_unless($role->project_id === null || $role->project_id === $project->id, 422);

        $project->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['access_role_id' => $role->id]);
    }
}
