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

    public function expenses()
    {
        return $this->hasMany(expence::class, 'invoice_id');
    }

    public function payments()
    {
        return $this->hasMany(invoice_payment::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
