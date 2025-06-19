<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainPanelWork extends Model
{
    protected $table = 'main_panel_works';

     /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

     protected $fillable = [
        'service_id',
        'on_grid_valtage',
        'on_grid_valtage_comments',
        'off_grid_valtage',
        'off_grid_valtage_comments',
        'invertor_service_fan_time',
        'invertor_service_fan_time_comments',
        'breaker_service',
        'breaker_service_comments',
        'DC_surge_arrestors',
        'DC_surge_arrestors_comments',
        'AC_surge_arrestors',
        'AC_surge_arrestors_comments',
        'invertor_connection_MC4_condition',
        'invertor_connection_MC4_condition_comments',
        'low_valtage_range',
        'low_valtage_range_comments',
        'high_valtage_range',
        'high_valtage_range_comments',
        'low_freaquence_range',
        'low_freaquence_range_comments',
        'high_freaquence_range',
        'high_freaquence_range_comments',
        'invertor_startup_time',
        'invertor_startup_time_comments',
        'e_today_invertor',
        'e_today_invertor_comments',
        'e_total_invertor',
        'e_total_invertor_comments',
        'power_bulb_blinking_style',
        'power_bulb_blinking_style_comments',
        'alta_vision_sticker',
        'alta_vision_sticker_comments',
        'wifi_config_done',
        'wifi_config_done_comments',
        'router_username',
        'router_username_comments',
        'router_password',
        'router_password_comments',
        'router_serial_number',
        'router_serial_number_comments',
        'took_photos',
        'took_photos_comments',
        'images'
     ];

      public function service()
     {
        return $this->belongsTo(Service::class);
     }
}
