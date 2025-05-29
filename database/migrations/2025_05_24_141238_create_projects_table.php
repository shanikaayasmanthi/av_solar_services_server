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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id'); 
            $table->foreign('customer_id')->references('user_id')->on('customers')->onDelete('restrict');
            $table->string('type');
            $table->string('project_name');
            $table->string('project_address');
            $table->string('neatest_town');
            $table->integer('no_of_panels');
            $table->double('panel_capacity');
            $table->integer('service_years_in_agreement');
            $table->integer('service_rounds_in_agreement');
            $table->dateTime('system_on')->nullable();
            $table->dateTime('project_installation_date')->nullable();
            $table->double('longitude');
            $table->double('lattitude');
            $table->string('location');
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
