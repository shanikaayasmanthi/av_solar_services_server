<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolarPanel extends Model
{
    protected $table = 'solar_panels';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'panel_type',
        'solar_panel_model',
        'panel_model_code',
        'wattage_of_panel',
        'no_of_panels',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
