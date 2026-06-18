<?php

namespace Modules\Access\Services;

use Modules\Workspace\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use Modules\Access\Enums\SystemRoleCode;
use Modules\Workspace\Models\Workspace;

class PermissionResolver
{
    public function userHasSystemRole(User $user, SystemRoleCode|string $roleCode): bool
    {
        $code = $roleCode instanceof SystemRoleCode ? $roleCode->value : $roleCode;

        return $user->systemRoles()
            ->where('code', $code)
            ->exists();
    }

    public function userCan(User $user, string $permissionCode): bool
    {
        if ($this->userHasSystemRole($user, SystemRoleCode::Admin)) {
            return true;
        }

        return $user->systemRoles()
            ->whereHas('permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();
    }

    public function userCanInWorkspace(User $user, Workspace $workspace, string $permissionCode): bool
    {
        if ($this->userCan($user, $permissionCode)) {
            return true;
        }

        if ($user->workspaceRole($workspace) === WorkspaceRole::Owner) {
            return true;
        }

        return $workspace->memberships()
            ->where('user_id', $user->id)
            ->whereHas('accessRole.permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();
    }

    public function userCanInProject(User $user, Project $project, string $permissionCode): bool
    {
        if ($this->userCan($user, $permissionCode)) {
            return true;
        }

        $hasProjectPermission = $project->memberships()
            ->where('user_id', $user->id)
            ->whereHas('accessRole.permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();

        return $hasProjectPermission;
    }
}
