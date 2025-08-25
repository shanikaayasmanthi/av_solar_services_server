<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
       public function up(): void
    {
        Schema::table('off_grid_hybrids', function (Blueprint $table) {
            // Make all specified columns nullable
            $table->string('wifi_username')->nullable()->change();
            $table->string('wifi_passowrd')->nullable()->change(); 
            $table->string('connection_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('off_grid_hybrids', function (Blueprint $table) {
            // Reverse: make columns not nullable
            $table->string('wifi_username')->nullable(false)->change();
            $table->string('wifi_passowrd')->nullable(false)->change();
            $table->string('connection_type')->nullable(false)->change();
        });
    }
};
