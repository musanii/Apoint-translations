<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Override;

class RefundSequence extends Model
{
    protected $fillable = ['year','last_number'];

    #[Override]
    protected function casts()
    {
        return[
            'year'=>'integer',
            'last_number'=>'integer',

        ];
    }
}
