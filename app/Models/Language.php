<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'native_name',
        'region',
        'is_active',
        'sort_order',

    ];

    #[Override]
    protected function casts()
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function sourcePricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'source_language_id');
    }

    public function targetPricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class, 'target_language_id');
    }
}
