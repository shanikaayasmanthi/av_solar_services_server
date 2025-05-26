<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Battery extends Model
{
    protected $table = 'batteries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'off_grid_hybrid_project_id',
        'battery_brand',
        'battery_model',
        'battery_capacity',
        'battery_serial_no'
    ];

    public function offGridHybrid()
    {
        return $this->belongsTo(OffGridHybrid::class);
    }
}
