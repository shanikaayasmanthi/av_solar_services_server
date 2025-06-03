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
        Schema::create('on_grids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->references('id')->on('projects')->onDelete('restrict');
            $table->bigInteger('on_grid_project_id');
            $table->string('electricity_bill_name');
            $table->string('wifi_username');
            $table->string('wifi_password');
            $table->string('harmonic_meter')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('on_grids');
    }
};
