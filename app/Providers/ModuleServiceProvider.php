<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Get the module name (used for view namespace).
     */
    abstract protected function moduleName(): string;

    /**
     * Bootstrap the module services.
     */
    public function boot(): void
    {
        $name = $this->moduleName();
        $path = $this->modulePath();

        $this->loadRoutesFrom("{$path}/routes.php");
        $this->loadMigrationsFrom("{$path}/database/migrations");
        $this->loadViewsFrom("{$path}/Resources/views", $name);
    }

    /**
     * Get the module base path.
     */
    protected function modulePath(): string
    {
        return dirname((new \ReflectionClass(static::class))->getFileName());
    }
}
