<?php

namespace Modules\Organization\Data\Enums;

enum OrganizationType: string
{
    case Company = 'company';
    case NonProfit = 'non_profit';
    case Government = 'government';
    case Educational = 'educational';
}
