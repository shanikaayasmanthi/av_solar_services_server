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
        Schema::create('outdoor_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->references('id')->on('services');
            $table->string('CEB_import_reading')->nullable();
            $table->string('CEB_import_reading_comments')->nullable();
            $table->string('CEB_export_reading')->nullable();
            $table->string('CEB_export_reading_comments')->nullable();
            $table->string('round_resistence')->nullable();
            $table->string('round_resistence_comments')->nullable();
            $table->boolean('earthing_rod_connection')->nullable();
            $table->string('earthing_rod_connection_comments')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outdoor_works');
    }
};
