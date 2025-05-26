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
        Schema::create('batteries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('off_grid_hybrid_project_id'); 
            $table->foreign('off_grid_hybrid_project_id')->references('off_grid_hybrid_project_id')->on('off_grid_hybrids');
            $table->string('battery_brand');
            $table->string('battery_model');
            $table->string('battery_capacity');
            $table->string('battery_serial_no');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batteries');
    }
};
