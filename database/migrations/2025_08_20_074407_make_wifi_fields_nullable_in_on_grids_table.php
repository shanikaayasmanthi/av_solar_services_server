<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::table('on_grids', function (Blueprint $table) {
            // Make all specified columns nullable
            $table->string('wifi_username')->nullable()->change();
            $table->string('wifi_password')->nullable()->change();
            $table->string('harmonic_meter')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('on_grids', function (Blueprint $table) {
            // Reverse: make columns not nullable
            $table->string('wifi_username')->nullable(false)->change();
            $table->string('wifi_password')->nullable(false)->change();
            $table->string('harmonic_meter')->nullable(false)->change();
        });
    }
};
