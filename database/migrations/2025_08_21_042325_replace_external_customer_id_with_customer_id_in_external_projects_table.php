<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('external_projects', function (Blueprint $table) {
            // Remove the foreign key constraint first
            $table->dropForeign(['external_customer_id']);
            
            // Add the new customer_id column
            $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            
            // Add foreign key constraint
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->onDelete('set null');
        });



        Schema::table('external_projects', function (Blueprint $table) {
            // Remove the old column after data migration
            $table->dropColumn('external_customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_projects', function (Blueprint $table) {
            // Add back the external_customer_id column
            $table->unsignedBigInteger('external_customer_id')->nullable()->after('id');
            
            // Add foreign key constraint for rollback
            $table->foreign('external_customer_id')
                  ->references('id')
                  ->on('external_customers')
                  ->onDelete('set null');
        });



        Schema::table('external_projects', function (Blueprint $table) {
            // Remove the customer_id column and its foreign key
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
