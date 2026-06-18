<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->renameTableIfExists('teams', 'workspaces');
        $this->renameTableIfExists('team_members', 'workspace_members');
        $this->renameTableIfExists('team_invitations', 'workspace_invitations');

        $this->renameColumnIfExists('users', 'current_team_id', 'current_workspace_id');
        $this->renameColumnIfExists('workspace_members', 'team_id', 'workspace_id');
        $this->renameColumnIfExists('workspace_invitations', 'team_id', 'workspace_id');
        $this->renameColumnIfExists('projects', 'team_id', 'workspace_id');
        $this->renameColumnIfExists('project_members', 'team_id', 'workspace_id');
        $this->renameColumnIfExists('crm_customers', 'team_id', 'workspace_id');

        $this->normalizeSqliteIndexNames();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->renameColumnIfExists('crm_customers', 'workspace_id', 'team_id');
        $this->renameColumnIfExists('project_members', 'workspace_id', 'team_id');
        $this->renameColumnIfExists('projects', 'workspace_id', 'team_id');
        $this->renameColumnIfExists('workspace_invitations', 'workspace_id', 'team_id');
        $this->renameColumnIfExists('workspace_members', 'workspace_id', 'team_id');
        $this->renameColumnIfExists('users', 'current_workspace_id', 'current_team_id');

        $this->renameTableIfExists('workspace_invitations', 'team_invitations');
        $this->renameTableIfExists('workspace_members', 'team_members');
        $this->renameTableIfExists('workspaces', 'teams');
    }

    private function renameTableIfExists(string $from, string $to): void
    {
        if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
            Schema::rename($from, $to);
        }
    }

    private function renameColumnIfExists(string $table, string $from, string $to): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $from) && ! Schema::hasColumn($table, $to)) {
            Schema::table($table, function (Blueprint $table) use ($from, $to): void {
                $table->renameColumn($from, $to);
            });
        }
    }

    private function normalizeSqliteIndexNames(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return;
        }

        foreach ([
            'teams_slug_unique' => 'CREATE UNIQUE INDEX IF NOT EXISTS "workspaces_slug_unique" ON "workspaces" ("slug")',
            'team_members_team_id_user_id_unique' => 'CREATE UNIQUE INDEX IF NOT EXISTS "workspace_members_workspace_id_user_id_unique" ON "workspace_members" ("workspace_id", "user_id")',
            'team_invitations_code_unique' => 'CREATE UNIQUE INDEX IF NOT EXISTS "workspace_invitations_code_unique" ON "workspace_invitations" ("code")',
            'projects_team_id_status_index' => 'CREATE INDEX IF NOT EXISTS "projects_workspace_id_status_index" ON "projects" ("workspace_id", "status")',
            'project_members_team_id_user_id_index' => 'CREATE INDEX IF NOT EXISTS "project_members_workspace_id_user_id_index" ON "project_members" ("workspace_id", "user_id")',
        ] as $oldIndex => $createSql) {
            DB::statement('DROP INDEX IF EXISTS "'.$oldIndex.'"');
            DB::statement($createSql);
        }
    }
};
