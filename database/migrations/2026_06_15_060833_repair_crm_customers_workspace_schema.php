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
        if (Schema::hasColumn('crm_customers', 'workspace_id')) {
            return;
        }

        Schema::table('crm_customers', function (Blueprint $table) {
            $table->foreignId('workspace_id')->after('id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name')->after('workspace_id');
            $table->string('bin_iin')->nullable()->after('name');
            $table->string('customer_type')->nullable()->after('bin_iin');
            $table->string('status')->default('active')->after('customer_type');
            $table->string('source')->nullable()->after('status');
            $table->string('phone')->nullable()->after('source');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->string('legal_address')->nullable()->after('website');
            $table->string('actual_address')->nullable()->after('legal_address');
            $table->foreignId('responsible_user_id')->nullable()->after('actual_address')->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable()->after('responsible_user_id');
            $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->softDeletes()->after('updated_at');

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
