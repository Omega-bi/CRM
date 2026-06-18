<?php

namespace Modules\Workspace\DTO;

readonly class WorkspacePermissions
{
    public function __construct(
        public bool $canUpdateWorkspace,
        public bool $canDeleteWorkspace,
        public bool $canAddMember,
        public bool $canUpdateMember,
        public bool $canRemoveMember,
        public bool $canCreateInvitation,
        public bool $canCancelInvitation,
    ) {
        //
    }
}
