<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invertor extends Model
{
    protected $table = 'invertors';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

     protected $fillable = [
        'project_id',
        'invertor_model_no',
        'invertor_check_code',
        'invertor_serial_no',
        'brand',
        'invertor_capacity'
     ];

     public function project()
     {
        return $this->belongsTo(Project::class);
     }
}
