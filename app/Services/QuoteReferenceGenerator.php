<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteSequence;
use Illuminate\Support\Facades\DB;

class QuoteReferenceGenerator
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            //Create a yearly sequence if it does not exist
            //The unique year constrait protects against duplicate rows
            DB::table('quote_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            //Lock the sequence row so another transaction
            //cannot allocate a number at the same time.
            $sequence = QuoteSequence::where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');
            return sprintf(
                'APT-Q-%d-%06d',
                $year,
                $sequence->last_number
            );
        });
    }
}
