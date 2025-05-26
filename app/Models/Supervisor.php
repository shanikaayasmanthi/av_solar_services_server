<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $table = 'supervisors';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'nic',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
