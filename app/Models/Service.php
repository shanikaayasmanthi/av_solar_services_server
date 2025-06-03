<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

     /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

     protected $fillable = [
        'project_id',
        'service_date',
        'service_time',
        'supervisor_id',
        'service_round_no',
        'service_type',
        'service_done',
        'power',
        'power_time',
        'wifi_connectivity',
        'capture_last_bill',
        'bill_image',
        'image_befor_service',
        'image_after_service',
        'remarks',
     ];

     public function project()
     {
        return $this->belongsTo(Project::class);
     }

     public function outdoorWork()
     {
      return $this->hasOne(OutdoorWork::class);
     }

     public function roofWork()
     {
      return $this->hasOne(RoofWork::class);
     }

     public function mainPanelWork()
     {
      return $this->hasOne(MainPanelWork::class);
     }

     public function dc()
     {
      return $this->hasOne(DC::class);
     }

     public function ac()
     {
      return $this->hasOne(AC::class);
     }

     public function serviceTechniciant(){
      return $this->hasMany(ServiceTechniciant::class);
     }
}


