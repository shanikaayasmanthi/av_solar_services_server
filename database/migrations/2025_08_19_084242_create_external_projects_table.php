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
        Schema::create('external_projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('external_customer_id'); 
            $table->foreign('external_customer_id')->references('id')->on('external_customer')->onDelete('restrict');
            $table->string('type');
            $table->string('project_name');
            $table->string('project_address');
            $table->string('nearest_town');
            $table->string('company_name');
            $table->integer('no_of_panels');
            $table->double('panel_capacity');
            $table->integer('service_years_in_agreement');
            $table->integer('service_rounds_in_agreement');
            $table->dateTime('system_on')->nullable();
            $table->dateTime('project_installation_date')->nullable();
            $table->double('longitude')->nullable();
            $table->double('lattitude')->nullable();
            $table->string('location')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_projects');
    }
};
