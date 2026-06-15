<?php

namespace Modules\Access\Actions;

use App\Models\User;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Models\Role;

class AssignSystemRole
{
    public function handle(User $user, Role $role): void
    {
        abort_unless($role->scope === RoleScope::System, 422);

        $user->systemRoles()->syncWithoutDetaching([$role->id]);
    }
}
