<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalProject extends Model
{
    protected $table = 'external_projects';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'customer_id',
        'type',
        'project_name',
        'project_address',
        'nearest_town',
        'company_name',
        'no_of_panels',
        'panel_capacity',
        'service_years_in_agreement',
        'service_rounds_in_agreement',
        'system_on',
        'project_installation_date',
        'longitude',
        'lattitude',
        'location',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'system_on' => 'datetime',
            'project_installation_date' => 'datetime',
            'longitude' => 'double',
            'lattitude' => 'double',
            'no_of_panels' => 'integer',
            'panel_capacity' => 'double',
            'service_years_in_agreement' => 'integer',
            'service_rounds_in_agreement' => 'integer',
        ];
    }

    /**
     * Get the customer that owns the project.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Get the on-grid details for the external project.
     */
    public function externalOnGrid(): HasOne
    {
        return $this->hasOne(ExternalOnGrid::class, 'external_project_id');
    }

    /**
     * Get the solar panels for the project.
     */
    public function solarPanels(): HasMany
    {
        return $this->hasMany(SolarPanel::class)->where('is_current', true);
    }

    /**
     * Get the inverters for the project.
     */
    public function inverters(): HasMany
    {
        return $this->hasMany(Invertor::class);
    }

    /**
     * Get the services for the project.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}