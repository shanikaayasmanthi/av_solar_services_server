<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_on_grids', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('on_grid_project_id', 'external_on_grid_project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('external_on_grids', function (Blueprint $table) {
            // Reverse the rename
            $table->renameColumn('external_on_grid_project_id', 'on_grid_project_id');
        });
    }
};
