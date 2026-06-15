<?php

namespace Modules\Workspace\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get all the permissions for this role.
     *
     * @return array<WorkspacePermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => WorkspacePermission::cases(),
            self::Admin => [
                WorkspacePermission::UpdateWorkspace,
                WorkspacePermission::CreateInvitation,
                WorkspacePermission::CancelInvitation,
            ],
            self::Member => [],
        };
    }

    /**
     * Determine if the role has the given permission.
     */
    public function hasPermission(WorkspacePermission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Get the hierarchy level for this role.
     */
    public function level(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Admin => 2,
            self::Member => 1,
        };
    }

    /**
     * Check if this role is at least as privileged as another role.
     */
    public function isAtLeast(WorkspaceRole $role): bool
    {
        return $this->level() >= $role->level();
    }

    /**
     * Get the roles that can be assigned to workspace members (excludes Owner).
     *
     * @return array<array{value: string, label: string}>
     */
    public static function assignable(): array
    {
        return collect(self::cases())
            ->filter(fn (self $role) => $role !== self::Owner)
            ->map(fn (self $role) => ['value' => $role->value, 'label' => $role->label()])
            ->values()
            ->toArray();
    }
}
