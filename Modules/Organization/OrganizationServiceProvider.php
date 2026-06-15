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
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
