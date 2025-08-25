<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExternalOffGridHybrid extends Model
{
    protected $table = 'external_off_grid_hybrids';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'external_project_id',
        'external_off_grid_hybrid_project_id',
        'wifi_username',
        'wifi_password', // Fixed typo from 'wifi_passowrd'
        'connection_type',
        'remarks'
    ];

    /**
     * Get the external project that owns the off-grid/hybrid details.
     */
    public function externalProject(): BelongsTo
    {
        return $this->belongsTo(ExternalProject::class, 'external_project_id');
    }

    /**
     * Get the battery for the external off-grid/hybrid system.
     */
    public function battery(): HasOne
    {
        return $this->hasOne(Battery::class, 'external_off_grid_hybrid_project_id', 'external_off_grid_hybrid_project_id');
    }
}