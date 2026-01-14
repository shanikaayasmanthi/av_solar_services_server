<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class invoice_payment extends Model
{
    //
    protected $table = 'invoice_payments';
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'payment_date',
        'amount'
    ];

    public function invoice()
    {
        return $this->belongsTo(invoice::class, 'invoice_id');
    }
    
}

