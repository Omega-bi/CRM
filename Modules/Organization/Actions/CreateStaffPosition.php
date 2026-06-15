<?php

namespace Modules\Organization\Actions;

use Modules\Organization\Models\StaffPosition;

class CreateStaffPosition
{
    /**
     * @param  array{department_id: int, name: string, planned_count?: int|null, sort_order?: int|null, is_active?: bool|null}  $data
     */
    public function handle(array $data): StaffPosition
    {
        return StaffPosition::create([
            'department_id' => $data['department_id'],
            'name' => $data['name'],
            'planned_count' => $data['planned_count'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }
}
