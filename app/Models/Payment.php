<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'total_payment',
        'paid_amount',
        'due_payment',
        'payment_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_payment' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_payment' => 'decimal:2',
    ];

    /**
     * Get the project that owns the payment.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}