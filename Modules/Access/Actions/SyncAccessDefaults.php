<?php

namespace Modules\Access\Actions;

use Modules\Access\Enums\RoleScope;
use Modules\Access\Enums\SystemRoleCode;
use Modules\Access\Models\Permission;
use Modules\Access\Models\Role;
use Modules\Access\Support\PermissionCatalog;

class SyncAccessDefaults
{
    public function __construct(private SyncRolePermissions $syncRolePermissions) {}

    public function handle(): void
    {
        foreach (PermissionCatalog::all() as $permission) {
            Permission::query()->updateOrCreate(
                ['code' => $permission['code']],
                [
                    'name' => $permission['name'],
                    'group' => $permission['group'],
                ],
            );
        }

        $adminRole = Role::query()->updateOrCreate(
            [
                'scope' => RoleScope::System,
                'code' => SystemRoleCode::Admin->value,
                'workspace_id' => null,
                'project_id' => null,
            ],
            [
                'name' => 'Administrator',
                'is_system' => true,
                'description' => 'Full system access.',
            ],
        );

        $this->syncRolePermissions->handle($adminRole, PermissionCatalog::codes());
    }
}
