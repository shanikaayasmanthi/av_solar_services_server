<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AC extends Model
{
    protected $table = 'a_c_s';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'OC_valtage_L1_N',
        'OC_valtage_L2_N',
        'OC_valtage_L3_N',
        'OC_valtage_L1_L2',
        'OC_valtage_L1_L3',
        'OC_valtage_L2_L3',
        'OC_valtage_N_E',
        'load_valtage_L1_N',
        'load_valtage_L2_N',
        'load_valtage_L3_N',
        'load_valtage_L1_L2',
        'load_valtage_L1_L3',
        'load_valtage_L2_L3',
        'load_valtage_N_E',
        'load_current_L1_N',
        'load_current_L2_N',
        'load_current_L3_N',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
