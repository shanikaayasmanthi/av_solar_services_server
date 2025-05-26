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
        Schema::create('d_c_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->references('id')->on('services');
            $table->double('OC_valtage_string_1')->nullable();
            $table->double('OC_valtage_string_2')->nullable();
            $table->double('OC_valtage_string_3')->nullable();
            $table->double('OC_valtage_string_4')->nullable();
            $table->double('OC_valtage_string_5')->nullable();
            $table->double('OC_valtage_string_6')->nullable();
            $table->double('OC_valtage_string_7')->nullable();
            $table->double('OC_valtage_string_8')->nullable();
            $table->double('load_valtage_string_1')->nullable();
            $table->double('load_valtage_string_2')->nullable();
            $table->double('load_valtage_string_3')->nullable();
            $table->double('load_valtage_string_4')->nullable();
            $table->double('load_valtage_string_5')->nullable();
            $table->double('load_valtage_string_6')->nullable();
            $table->double('load_valtage_string_7')->nullable();
            $table->double('load_valtage_string_8')->nullable();
            $table->double('load_current_string_1')->nullable();
            $table->double('load_current_string_2')->nullable();
            $table->double('load_current_string_3')->nullable();
            $table->double('load_current_string_4')->nullable();
            $table->double('load_current_string_5')->nullable();
            $table->double('load_current_string_6')->nullable();
            $table->double('load_current_string_7')->nullable();
            $table->double('load_current_string_8')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('d_c_s');
    }
};
