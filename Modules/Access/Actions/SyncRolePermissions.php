<?php

namespace Modules\Access\Actions;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Support\PermissionCatalog;

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
            ->intersect(PermissionCatalog::codes())
            ->whenNotEmpty(fn (Collection $codes) => Permission::query()->whereIn('code', $codes)->pluck('id'))
            ->values()
            ->all();

        if (count($permissionIds) !== collect($permissionCodes)->filter()->unique()->intersect(PermissionCatalog::codes())->count()) {
            throw ValidationException::withMessages([
                'permissionCodes' => __('Some selected permissions are not available.'),
            ]);
        }

        $role->permissions()->sync($permissionIds);

        return $role->load('permissions');
    }
}
