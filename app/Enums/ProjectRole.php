<?php

namespace App\Enums;

enum ProjectRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Observer = 'observer';
    case Director = 'director';
    case TechnicalSupervisor = 'technical_supervisor';
    case Foreman = 'foreman';
    case Estimator = 'estimator';
    case Accountant = 'accountant';
    case Supplier = 'supplier';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
            self::Observer => 'Observer',
            self::Director => 'Director',
            self::TechnicalSupervisor => 'Technical supervisor',
            self::Foreman => 'Foreman',
            self::Estimator => 'Estimator',
            self::Accountant => 'Accountant',
            self::Supplier => 'Supplier',
        };
    }
}
