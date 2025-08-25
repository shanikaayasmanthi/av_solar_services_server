<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Temporary disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('external_customer', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['user_id']);
            
            // Drop the column
            $table->dropColumn('user_id');
        });

        // Add the column properly with correct constraints
        Schema::table('external_customer', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->after('id')
                  ->constrained('users')
                  ->onDelete('restrict')
                  ->unique();
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('external_customer', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // Re-add as nullable
        Schema::table('external_customer', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('restrict');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
