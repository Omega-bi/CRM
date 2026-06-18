<?php

namespace Modules\Project;

use App\Providers\ModuleServiceProvider;

class ProjectServiceProvider extends ModuleServiceProvider
{
    /**
     * Get the module name (used for view namespace).
     */
    protected function moduleName(): string
    {
        return 'project';
    }

    /**
     * Register the module services.
     */
    public function register(): void
    {
        // Register any module-specific bindings here
    }
}
