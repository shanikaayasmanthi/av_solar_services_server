<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DC extends Model
{
    protected $table = 'd_c_s';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_id',
        'OC_valtage_string_1',
        'OC_valtage_string_2',
        'OC_valtage_string_3',
        'OC_valtage_string_4',
        'OC_valtage_string_5',
        'OC_valtage_string_6',
        'OC_valtage_string_7',
        'OC_valtage_string_8',
        'load_valtage_string_1',
        'load_valtage_string_2',
        'load_valtage_string_3',
        'load_valtage_string_4',
        'load_valtage_string_5',
        'load_valtage_string_6',
        'load_valtage_string_7',
        'load_valtage_string_8',
        'load_current_string_1',
        'load_current_string_2',
        'load_current_string_3',
        'load_current_string_4',
        'load_current_string_5',
        'load_current_string_6',
        'load_current_string_7',
        'load_current_string_8'
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
