<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class invoice extends Model
{
    //
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'created_by',
        'project_id',
        'invoice_date',
        'amount',
        'discount',
        'tax',
        'total',
        'notes'
    ];
}
