<?php

namespace Modules\Employee\Data\Enums;

enum EmploymentStatus: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Intern = 'intern';
}
