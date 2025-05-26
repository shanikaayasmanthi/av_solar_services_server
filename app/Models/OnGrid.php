<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnGrid extends Model
{
    protected $table = 'on_grids';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

     protected $fillable = [
        'project_id',
        'on_grid_project_id',
        'electricity_bill_name',
        'wifi_username',
        'wifi_password',
        'harmonic_meter',
        'logitude',
        'lattitude',
        'remarks'
     ];

     public function project()
     {
        return $this->belongsTo(Project::class);;
     }
}
