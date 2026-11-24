<?php

namespace App\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class PaymentReconciliationEvent extends Model
{
    protected $fillable = [
        'payment_id',
        'user_id',
        'event_type',
        'outcome',
        'message',
        'attempt_number',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
