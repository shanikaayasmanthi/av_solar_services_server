<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class expence extends Model
{
    protected $table = 'expences';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'invoice_id',
        'expense_type',
        'amount'
    ];
}
