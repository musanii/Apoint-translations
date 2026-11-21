<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class Refund extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'payment_id',
        'reference',
        'provider_reference',
        'amount',
        'status',
        'reason',
        'failure_reason',
        'initiated_at',
        'completed_at',
    ];

    #[Override]
    protected function casts()
    {
        return [
            'amount' => 'decimal:2',
            'initiated_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
