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
        Schema::create('roof_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->references('id')->on('services');
            $table->boolean('cloudness_reading')->nullable();
            $table->string('cloudness_reading_comments');
            $table->boolean('panel_service')->nullable();
            $table->string('panel_service_comments')->nullable();
            $table->boolean('structure_service')->nullable();
            $table->string('structure_service_comments')->nullable();
            $table->boolean('nut_bolt_condition')->nullable();
            $table->string('nut_bolt_condition_comments');
            $table->boolean('shadow')->nullable();
            $table->string('shadow_comments')->nullable();
            $table->boolean('panel_MC4_condition')->nullable();
            $table->string('panel_MC4_condition_comments')->nullable();
            $table->boolean('took_photos')->nullable();
            $table->string('took_photos_comments')->nullable();
            $table->string('photos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roof_works');
    }
};
