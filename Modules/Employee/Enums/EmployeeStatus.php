<?php

namespace Modules\Employee\Enums;

enum EmployeeStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Archived = 'archived';
}
