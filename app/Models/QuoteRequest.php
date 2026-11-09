<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteRequest extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'source_language_id',
        'target_language_id',
        'currency_id',
        'quantity',
        'unit',
        'delivery_date',
        'instructions',
        'status',
        'delivery_method',
        'recipient_name',
        'recipient_phone',
        'delivery_country',
        'delivery_address',
        'delivery_postal_code',
    ];


    protected $attributes = [
        'status' => 'pending'
    ];
    protected function casts()
    {
        return [
            'quantity' => 'integer',
            'delivery_date' => 'date'
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function sourceLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'source_language_id');
    }

    public function targetLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_language_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(QuoteRequestDocument::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
