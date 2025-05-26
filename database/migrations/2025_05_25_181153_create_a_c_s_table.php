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
        Schema::create('a_c_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->references('id')->on('services');
            $table->double('OC_valtage_L1_N')->nullable();
            $table->double('OC_valtage_L2_N')->nullable();
            $table->double('OC_valtage_L3_N')->nullable();
            $table->double('OC_valtage_L1_L2')->nullable();
            $table->double('OC_valtage_L1_L3')->nullable();
            $table->double('OC_valtage_L2_L3')->nullable();
            $table->double('OC_valtage_N_E')->nullable();
            $table->double('load_valtage_L1_N')->nullable();
            $table->double('load_valtage_L2_N')->nullable();
            $table->double('load_valtage_L3_N')->nullable();
            $table->double('load_valtage_L1_L2')->nullable();
            $table->double('load_valtage_L1_L3')->nullable();
            $table->double('load_valtage_L2_L3')->nullable();
            $table->double('load_valtage_N_E')->nullable();
            $table->double('load_current_L1_N')->nullable();
            $table->double('load_current_L2_N')->nullable();
            $table->double('load_current_L3_N')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_c_s');
    }
};
