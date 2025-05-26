<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoofWork extends Model
{
    protected $table = 'roof_works';

     /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

     protected $fillable = [
        'service_id',
        'cloudness_reading',
        'cloudness_reading_comments',
        'panel_service',
        'panel_service_comments',
        'structure_service',
        'structure_service_comments',
        'nut_bolt_condition',
        'nut_bolt_condition_comments',
        'shadow',
        'shadow_comments',
        'panel_MC4_condition',
        'panel_MC4_condition_comments',
        'took_photos',
        'took_photos_comments',
        'photos'
     ];

     public function service()
     {
        return $this->belongsTo(Service::class);
     }
}
