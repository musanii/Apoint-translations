<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

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
    ];


  protected $attributes = [
    'status'=>'pending'
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
}
