<?php

namespace Modules\Access\Actions;

use Modules\Access\Models\Permission;

class CreatePermission
{
    /**
     * @param  array{code: string, name: string, group?: string|null}  $data
     */
    public function handle(array $data): Permission
    {
        return Permission::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'group' => $data['group'] ?? null,
        ]);
    }
}
