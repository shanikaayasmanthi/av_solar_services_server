<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('external_customer', function (Blueprint $table) {
            // First, check if the column exists and add it if not
            if (!Schema::hasColumn('external_customer', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            } else {
                // If column exists, make sure it's the right type
                $table->unsignedBigInteger('user_id')->nullable()->change();
            }
            
            // Add foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_customer', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['user_id']);
            
            // You can choose to keep or remove the column in rollback
            // If you want to remove it:
            // $table->dropColumn('user_id');
        });
    }
};
