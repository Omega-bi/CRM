<?php

namespace Modules\Access;

use App\Providers\ModuleServiceProvider;

class AccessServiceProvider extends ModuleServiceProvider
{
    /**
     * Get the module name (used for view namespace).
     */
    protected function moduleName(): string
    {
        return 'access';
    }
}
