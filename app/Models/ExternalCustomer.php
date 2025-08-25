<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalCustomer extends Model
{
    protected $table = 'external_customer';

    protected $fillable = [
        'name',
        'nic',
        'email',
        'phone_no',
        'address',
        'user_id'
    ];

   //public $timestamps = false;

public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}


}
