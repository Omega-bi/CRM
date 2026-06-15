<?php

namespace Modules\Workspace\Actions;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    /**
     * Create a new workspace and add the user as owner.
     */
    public function handle(User $user, string $name, bool $isPersonal = false): Workspace
    {
        return DB::transaction(function () use ($user, $name, $isPersonal) {
            $workspace = Workspace::create([
                'name' => $name,
                'is_personal' => $isPersonal,
            ]);

            $workspace->memberships()->create([
                'user_id' => $user->id,
                'role' => WorkspaceRole::Owner,
            ]);

            $user->switchWorkspace($workspace);

            return $workspace;
        });
    }
}
