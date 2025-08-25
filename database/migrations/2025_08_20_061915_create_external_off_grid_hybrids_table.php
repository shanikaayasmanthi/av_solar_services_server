<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_off_grid_hybrids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_project_id');
            $table->string('external_off_grid_hybrid_project_id')->nullable();
            $table->string('wifi_username')->nullable();
            $table->string('wifi_password')->nullable(); // Fixed typo from 'wifi_passowrd'
            $table->string('connection_type')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('external_project_id')
                  ->references('id')
                  ->on('external_projects')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_off_grid_hybrids');
    }
};
