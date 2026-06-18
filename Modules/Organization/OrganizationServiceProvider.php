<?php

namespace Modules\Organization;

use Illuminate\Support\ServiceProvider;

class OrganizationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the organization module.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/routes.php');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadViewsFrom(__DIR__.'/Resources/views', 'organization');
    }
}
