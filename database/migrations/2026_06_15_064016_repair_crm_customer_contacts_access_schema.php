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
        if (Schema::hasColumn('crm_customer_contacts', 'customer_id')) {
            return;
        }

        Schema::table('crm_customer_contacts', function (Blueprint $table) {
            $table->foreignId('customer_id')->after('id')->constrained('crm_customers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('full_name')->after('user_id');
            $table->string('phone')->nullable()->after('full_name');
            $table->string('email')->nullable()->after('phone');
            $table->string('position')->nullable()->after('email');
            $table->string('status')->default('active')->after('position')->index();
            $table->text('notes')->nullable()->after('status');
            $table->softDeletes()->after('updated_at');

            $table->index(['customer_id', 'status']);
            $table->index('user_id');
            $table->index('email');
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
