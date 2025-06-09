<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceTechniciant extends Model
{
    protected $table = "service_techniciants";

    protected $fillable = [
        "service_id",
        "techniciant_name",
    ];
    public function service(){
        return $this->belongsTo(Service::class);
    }
}
