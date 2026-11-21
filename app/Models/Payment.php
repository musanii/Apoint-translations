<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';


    protected $fillable = [
        'order_id',
        'currency_id',
        'reference',
        'provider',
        'payment_method',
        'provider_channel_id',
        'provider_reference',
        'provider_transaction_reference',
        'provider_initiation_started_at',
        'provider_initiation_started_at',
        'amount',
        'status',
        'failure_reason',
        'paid_at',
        'provider_checkout_request_id',
        'provider_merchant_request_id',
        'reconciliation_attempts',
        'last_reconciled_at',
        'next_reconciliation_at',
    ];

    #[Override]
    protected function casts()
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'reconciliation_attempts' => 'integer',
            'last_reconciled_at' => 'datetime',
            'next_reconciliation_at' => 'datetime',
            'provider_initiation_started_at' => 'datetime',
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
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
