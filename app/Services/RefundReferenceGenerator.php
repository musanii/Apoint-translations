<?php

namespace App\Services;

use App\Models\RefundSequence;
use Illuminate\Support\Facades\DB;

class RefundReferenceGenerator
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            DB::table('refund_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = RefundSequence::where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $sequence->increment('last_number');

            return sprintf(
                'APT-R-%d-%06d',
                $year,
                $sequence->last_number
            );
        });
    }
}
