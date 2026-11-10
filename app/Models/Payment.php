<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'ppending';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';


    protected $fillable = [
        'order_id',
        'currency_id',
        'reference',
        'provider',
        'provider_reference',
        'amount',
        'status',
        'failure_reason',
        'paid_at'
    ];

    #[Override]
    protected function casts()
    {
        return [
            'amount'=>'decimal:2',
            'paid_at'=>'datetime',

        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
