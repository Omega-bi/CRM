<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'scope')) {
                $table->string('scope')->default('workspace')->after('workspace_id')->index();
            }
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'role_id']);
        });

        Schema::table('workspace_members', function (Blueprint $table) {
            if (! Schema::hasColumn('workspace_members', 'access_role_id')) {
                $table->foreignId('access_role_id')
                    ->nullable()
                    ->after('position')
                    ->constrained('roles')
                    ->nullOnDelete();
            }
        });

        Schema::table('project_members', function (Blueprint $table) {
            if (! Schema::hasColumn('project_members', 'access_role_id')) {
                $table->foreignId('access_role_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('roles')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_members', function (Blueprint $table) {
            if (Schema::hasColumn('project_members', 'access_role_id')) {
                $table->dropConstrainedForeignId('access_role_id');
            }
        });

        Schema::table('workspace_members', function (Blueprint $table) {
            if (Schema::hasColumn('workspace_members', 'access_role_id')) {
                $table->dropConstrainedForeignId('access_role_id');
            }
        });

        Schema::dropIfExists('user_roles');

        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'scope')) {
                $table->dropColumn('scope');
            }
        });
    }
};
