<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    /** @use HasFactory<\Database\Factories\UserTypeFactory> */
    use HasFactory;

    protected $table = 'user_types';

    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    
}
