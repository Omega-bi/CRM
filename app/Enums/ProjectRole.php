<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Director = 'director';
    case TechnicalSupervisor = 'technical_supervisor';
    case Foreman = 'foreman';
    case Estimator = 'estimator';
    case Accountant = 'accountant';
    case Supplier = 'supplier';
    case Observer = 'observer';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Director => 'Director',
            self::TechnicalSupervisor => 'Technical supervisor',
            self::Foreman => 'Foreman',
            self::Estimator => 'Estimator',
            self::Accountant => 'Accountant',
            self::Supplier => 'Supplier',
            self::Observer => 'Observer',
        };
    }
}
