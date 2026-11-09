<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{

use HasFactory; 

    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';


    protected $fillable = [
        'quote_id',
        'user_id',
        'currency_id',
        'reference',
        'subtotal',
        'express_fee',
        'certification_fee',
        'courier_fee',
        'total',
        'delivery_method',
        'recipient_name',
        'recipient_phone',
        'delivery_country',
        'delivery_address',
        'delivery_postal_code',
        'status',
        'payment_status',
        'paid_at',

    ];


    #[Override]
    protected function casts()
    {
        return [
            'subtotal' => 'decimal:2',
            'express_fee' => 'decimal:2',
            'certification_fee' => 'decimal:2',
            'courier_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class,'currency_id');
    }
}
