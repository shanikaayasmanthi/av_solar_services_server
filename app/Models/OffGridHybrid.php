<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OffGridHybrid extends Model
{
    protected $table = 'off_grid_hybrids';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'off_grid_hybrid_project_id',
        'wifi_username',
        'wifi_passowrd',
        'connection_type',
        'remarks'
    ];

    public function project()
     {
        return $this->belongsTo(Project::class);;
     }

     public function battery()
     {
        return $this->hasOne(Battery::class);
     }
}
