<?php

namespace Modules\Access\Data\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Member = 'member';
    case Guest = 'guest';
}
