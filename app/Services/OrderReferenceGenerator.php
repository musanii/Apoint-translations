<?php

namespace App\Services;

use App\Models\OrderSequence;
use Illuminate\Support\Facades\DB;

class OrderReferenceGenerator
{
    public function generate(): string
    {
        return DB::transaction(function () {
            $year = now()->year;

            DB::table('order_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = OrderSequence::where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
            $sequence->increment('last_number');

            return sprintf(
                'APT-O-%d-%06d',
                $year,
                $sequence->last_number
            );
        });
    }
}
