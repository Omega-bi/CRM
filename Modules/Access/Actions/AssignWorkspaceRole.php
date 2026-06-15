<?php

namespace Modules\Access\Actions;

use App\Models\User;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Models\Role;
use Modules\Workspace\Models\Workspace;

class AssignWorkspaceRole
{
    public function handle(Workspace $workspace, User $user, Role $role): void
    {
        abort_unless($role->scope === RoleScope::Workspace, 422);
        abort_unless($role->workspace_id === null || $role->workspace_id === $workspace->id, 422);

        $workspace->memberships()
            ->where('user_id', $user->id)
            ->firstOrFail()
            ->update(['access_role_id' => $role->id]);
    }
}
