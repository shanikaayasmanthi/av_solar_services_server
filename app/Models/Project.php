<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

     protected $fillable = [
        'customer_id',
        'type',
        'nearest_town',
        // 'project_no',
        'no_of_panels',
        'panel_capacity',
        'service_years_in_agreement',
        'service_rounds_in_agreement',
        'system_on',
        'project_installation_date',
        'remarks',
        
     ];

     public function customer()
     {
      return $this->belongsTo(Customer::class);
     }

     public function solarPanel()
     {
      return $this->hasMany(SolarPanel::class);
     }

     public function onGrid()
     {
      return $this->hasOne(OnGrid::class);
     }

     public function offGridHybrid()
     {
      return $this->hasOne(OffGridHybrid::class);
     }

     public function invertor()
     {
      return $this->hasMany(Invertor::class);
     }

     public function service()
     {
      return $this->hasMany(Service::class);
     }

   //   public function OutdoorWork()
   //   {
   //    return $this->hasMany(OutdoorWork::class);
   //   }
}
