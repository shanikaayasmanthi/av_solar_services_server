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
        Schema::table('projects', function (Blueprint $table) {
            // Make longitude and latitude nullable
            $table->double('longitude')->nullable()->change();
            $table->double('lattitude')->nullable()->change();
            
            // Change default value of isInstalled to false
            $table->boolean('isInstalled')->default(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Reverse: make longitude and latitude not nullable
            $table->double('longitude')->nullable(false)->change();
            $table->double('lattitude')->nullable(false)->change();
            
            // Reverse: change default value back to true (or remove default)
            $table->boolean('isInstalled')->default(true)->change();
        });
    }
};
