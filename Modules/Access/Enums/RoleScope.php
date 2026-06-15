<?php

namespace Modules\Access\Enums;

enum RoleScope: string
{
    case System = 'system';
    case Workspace = 'workspace';
    case Project = 'project';
}
