<?php

namespace Modules\Access\Actions;

use Illuminate\Support\Collection;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;

class SyncRolePermissions
{
    /**
     * @param  iterable<string>  $permissionCodes
     */
    public function handle(Role $role, iterable $permissionCodes): Role
    {
        $permissionIds = collect($permissionCodes)
            ->filter()
            ->unique()
            ->whenNotEmpty(fn (Collection $codes) => Permission::whereIn('code', $codes)->pluck('id'))
            ->values()
            ->all();

        $role->permissions()->sync($permissionIds);

        return $role->load('permissions');
    }
}
