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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->references('id')->on('projects')->onDelete('restrict');
            $table->unsignedBigInteger('supervisor_id');
            $table->foreign('supervisor_id')->references('user_id')->on('supervisors');
            $table->dateTime('service_date');
            $table->string('service_time')->nullable();
            $table->integer('service_round_no');
            $table->string('service_type');
            $table->boolean('service_done')->default(false);
            $table->double('power')->nullable();
            $table->timestamp('power_time')->nullable();
            $table->boolean('wifi_connectivity')->nullable();
            $table->boolean('capture_last_bill')->nullable();
            $table->string('bill_image')->nullable();
            $table->string('image_befor_service')->nullable();
            $table->string('image_after_service')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
