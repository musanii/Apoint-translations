<?php

namespace App\Models;

use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'quote_request_id',
        'currency_id',
        'reference',
        'pricing_type',
        'quantity',
        'unit',
        'rate',
        'subtotal',
        'express_fee',
        'certification_fee',
        'courier_fee',
        'total',
        'admin_notes',
        'customer_notes',
        'status',
        'sent_at',
        'valid_until',
        'accepted_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'rate' => 'decimal:4',
            'subtotal' => 'decimal:2',
            'express_fee' => 'decimal:2',
            'certification_fee' => 'decimal:2',
            'courier_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'valid_until' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function quoteRequest(): BelongsTo<QuoteRequest, $this><QuoteRequest, $this>
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function currency(): BelongsTo<Currency, $this><Currency, $this>
    {
        return $this->belongsTo(Currency::class);
    }
}
