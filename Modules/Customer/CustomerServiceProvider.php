<?php

namespace Modules\Customer;

use App\Providers\ModuleServiceProvider;

class CustomerServiceProvider extends ModuleServiceProvider
{
    /**
     * Get the module name (used for view namespace).
     */
    protected function moduleName(): string
    {
        return 'customer';
    }
}
