<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'decimal_places',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }
}
