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
        Schema::create('off_grid_hybrids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->references('id')->on('projects')->onDelete('restrict');
            $table->bigInteger('off_grid_hybrid_project_id');
            $table->string('wifi_username')->nullable();
            $table->string('wifi_passowrd')->nullable();
            $table->string('connection_type');
            $table->string('longitude')->nullable();
            $table->string('lattitude')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('off_grid_hybrids');
    }
};
