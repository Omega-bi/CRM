<?php

namespace Modules\Access\Actions;

use Illuminate\Support\Str;
use Modules\Access\Enums\RoleScope;
use Modules\Access\Models\Role;

class CreateRole
{
    /**
     * @param  array{name: string, code?: string|null, scope: RoleScope|string, workspace_id?: int|null, project_id?: int|null, description?: string|null, is_system?: bool|null}  $data
     */
    public function handle(array $data): Role
    {
        return Role::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? Str::slug($data['name']),
            'scope' => $data['scope'],
            'workspace_id' => $data['workspace_id'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'description' => $data['description'] ?? null,
            'is_system' => $data['is_system'] ?? false,
        ]);
    }
}
