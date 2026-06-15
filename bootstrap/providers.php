<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Modules\Access\AccessServiceProvider;
use Modules\Customer\CustomerServiceProvider;
use Modules\Employee\EmployeeServiceProvider;
use Modules\Organization\OrganizationServiceProvider;
use Modules\Workspace\WorkspaceServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    AccessServiceProvider::class,
    WorkspaceServiceProvider::class,
    EmployeeServiceProvider::class,
    OrganizationServiceProvider::class,
    CustomerServiceProvider::class,
];
