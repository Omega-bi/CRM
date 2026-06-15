<?php

namespace Modules\Customer\Actions;

use App\Models\Project;
use InvalidArgumentException;
use Modules\Customer\Models\Customer;

class AttachProjectCustomer
{
    public function handle(Project $project, Customer $customer, ?string $role = null): void
    {
        if ($project->workspace_id !== $customer->workspace_id) {
            throw new InvalidArgumentException('Project and customer must belong to the same workspace.');
        }

        $project->customers()->syncWithoutDetaching([
            $customer->id => [
                'workspace_id' => $project->workspace_id,
                'role' => $role,
            ],
        ]);
    }
}
