<?php

namespace Modules\Workspace;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\Workspace\Models\Workspace;
use Modules\Workspace\Policies\WorkspacePolicy;

class WorkspaceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the workspace module.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'workspace');

        Livewire::addNamespace('workspace', __DIR__.'/Resources/views/livewire');

        Blade::component('workspace::components.invitation-alert', 'workspace-invitation-alert');

        Gate::policy(Workspace::class, WorkspacePolicy::class);
    }
}
