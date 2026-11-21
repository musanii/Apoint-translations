<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteSequence extends Model
{
    protected $fillable = [
        'year',
        'last_number',
    ];

    protected function casts()
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
