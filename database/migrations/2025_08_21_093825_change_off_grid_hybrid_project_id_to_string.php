<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
    {
        Schema::table('off_grid_hybrids', function (Blueprint $table) {
            $table->string('off_grid_hybrid_project_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('off_grid_hybrids', function (Blueprint $table) {
            $table->integer('off_grid_hybrid_project_id')->change();
        });
    }
};
