<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Suspended = 'suspended';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return ucfirst($this->value);
    }
}
