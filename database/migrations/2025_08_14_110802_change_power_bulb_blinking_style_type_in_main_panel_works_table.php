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
        Schema::table('main_panel_works', function (Blueprint $table) {
            // Convert from int to string
            $table->string('power_bulb_blinking_style')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_panel_works', function (Blueprint $table) {
            // Rollback to int if needed
            $table->integer('power_bulb_blinking_style')->nullable()->change();
        });
    }
};
