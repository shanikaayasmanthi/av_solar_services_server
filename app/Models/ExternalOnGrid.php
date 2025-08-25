<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalOnGrid extends Model
{
    protected $table = 'external_on_grids';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'external_project_id',
        'external_on_grid_project_id', // Changed here
        'electricity_bill_name',
        'wifi_username',
        'wifi_password',
        'harmonic_meter',
        'remarks'
    ];

    /**
     * Get the external project that owns the on-grid details.
     */
    public function externalProject(): BelongsTo
    {
        return $this->belongsTo(ExternalProject::class, 'external_project_id');
    }
}