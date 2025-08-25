<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
    {
        Schema::table('on_grids', function (Blueprint $table) {
            $table->string('on_grid_project_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('on_grids', function (Blueprint $table) {
            $table->integer('on_grid_project_id')->change();
        });
    }
};
