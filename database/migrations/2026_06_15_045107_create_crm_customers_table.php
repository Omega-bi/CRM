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
        Schema::create('crm_customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();

            $table->string('name');
            $table->string('bin_iin')->nullable();

            $table->string('customer_type')->nullable();
            // государственный, частный, застройщик, генеральный подрядчик, субподрядчик

            $table->string('status')->default('active');
            // lead, active, archived, blacklisted

            $table->string('source')->nullable();
            // tender, referral, website, direct, cold_call

            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            $table->string('legal_address')->nullable();
            $table->string('actual_address')->nullable();

            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_customers');
    }
};
