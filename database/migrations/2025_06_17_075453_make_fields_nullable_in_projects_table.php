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
            //
             $table->double('panel_capacity')->nullable()->change();
            $table->string('project_name')->nullable()->change();
            $table->double('longitude')->nullable()->change();
            $table->double('lattitude')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->double('panel_capacity')->nullable(false)->change();
            $table->string('project_name')->nullable(false)->change();
            $table->double('longitude')->nullable(false)->change();
            $table->double('lattitude')->nullable(false)->change();
        });
    }
};
