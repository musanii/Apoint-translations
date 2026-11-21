<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    protected $fillable = [
        'service_id',
        'currency_id',
        'source_language_id',
        'target_language_id',
        'pricing_type',
        'unit',
        'rate',
        'minimum_charge',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'minimum_charge' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function service(): BelongsTo<Service, $this>
    {
        return $this->belongsTo(Service::class);
    }

    public function currency(): BelongsTo<Currency, $this>
    {
        return $this->belongsTo(Currency::class);
    }

    public function sourceLanguage(): BelongsTo<Language, $this>
    {
        return $this->belongsTo(Language::class, 'source_language_id');
    }

    public function targetLanguage(): BelongsTo<Language, $this>
    {
        return $this->belongsTo(Language::class, 'target_language_id');
    }
}
