<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPhoneNo extends Model
{
    protected $table = "customer_phone_nos";

    protected $fillable = [
        "customer_id",
        "phone_no"
    ] ;

        public function customer(){
        return $this->belongsTo(Customer::class);
    }
}
