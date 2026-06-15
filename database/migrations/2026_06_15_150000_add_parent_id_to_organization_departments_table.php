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
        Schema::table('organization_departments', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('is_active')
                ->constrained('organization_departments')
                ->nullOnDelete();

            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organization_departments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
