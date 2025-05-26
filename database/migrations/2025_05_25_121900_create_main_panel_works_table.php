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
        Schema::create('main_panel_works', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->references('id')->on('services');
            $table->string('on_grid_valtage')->nullable();
            $table->string('on_grid_valtage_comments')->nullable();
            $table->string('off_grid_valtage')->nullable();
            $table->string('off_grid_valtage_comments')->nullable();
            $table->boolean('invertor_service_fan_time')->nullable();
            $table->string('invertor_service_fan_time_comments')->nullable();
            $table->boolean('breaker_service')->nullable();
            $table->string('breaker_service_comments')->nullable();
            $table->boolean('DC_surge_arrestors')->nullable();
            $table->string('DC_surge_arrestors_comments')->nullable();
            $table->boolean('AC_surge_arrestors')->nullable();
            $table->string('AC_surge_arrestors_comments')->nullable();
            $table->boolean('invertor_connection_MC4_condition')->nullable();
            $table->string('invertor_connection_MC4_condition_comments')->nullable();
            $table->string('low_valtage_range')->nullable();
            $table->string('low_valtage_range_comments')->nullable();
            $table->string('high_valtage_range')->nullable();
            $table->string('high_valtage_range_comments')->nullable();
            $table->string('low_freaquence_range')->nullable();
            $table->string('low_freaquence_range_comments')->nullable();
            $table->string('high_freaquence_range')->nullable();
            $table->string('high_freaquence_range_comments')->nullable();
            $table->string('invertor_startup_time')->nullable();
            $table->string('invertor_startup_time_comments')->nullable();
            $table->string('e_today_invertor')->nullable();
            $table->string('e_today_invertor_comments')->nullable();
            $table->string('e_total_invertor')->nullable();
            $table->string('e_total_invertor_comments')->nullable();
            $table->boolean('power_bulb_blinking_style')->nullable();
            $table->string('power_bulb_blinking_style_comments')->nullable();
            $table->boolean('alta_vision_sticker')->nullable();
            $table->string('alta_vision_sticker_comments')->nullable();
            $table->boolean('wifi_config_done')->nullable();
            $table->string('wifi_config_done_comments')->nullable();
            $table->string('router_username')->nullable();
            $table->string('router_username_comments')->nullable();
            $table->string('router_password')->nullable();
            $table->string('router_password_comments')->nullable();
            $table->string('router_serial_number')->nullable();
            $table->string('router_serial_number_comments')->nullable();
            $table->boolean('took_photos')->nullable();
            $table->string('took_photos_comments')->nullable();
            $table->string('images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_panel_works');
    }
};
