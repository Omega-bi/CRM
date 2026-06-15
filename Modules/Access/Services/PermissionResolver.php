<?php

namespace Modules\Access\Services;

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
        return $user->systemRoles()
            ->whereHas('permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();
    }

    public function userCanInWorkspace(User $user, Workspace $workspace, string $permissionCode): bool
    {
        return $workspace->memberships()
            ->where('user_id', $user->id)
            ->whereHas('accessRole.permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();
    }

    public function userCanInProject(User $user, Project $project, string $permissionCode): bool
    {
        return $project->memberships()
            ->where('user_id', $user->id)
            ->whereHas('accessRole.permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();
    }
}
