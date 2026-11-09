<?php

namespace App\Services;

use App\Models\Quote;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;

class QuoteStatusService
{
    public function send(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()
                ->lockForUpdate()
                ->findOrFail($quote->id);

            if ($quote->status !== Quote::STATUS_DRAFT) {
                throw new InvalidArgumentException('Only draft quotes can be sent.');
            }

            $quote->update([
                'status' => Quote::STATUS_SENT,
                'sent_at' => now(),
            ]);

            return $quote->refresh();
        });
    }

    public function accept(Quote $quote): Quote

    {

        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()
                ->lockForUpdate()
                ->findOrFail($quote->id);


            if ($quote->status !== Quote::STATUS_SENT) {
                throw new InvalidArgumentException('Only sent quotes can be accepted.');
            }
            $quote->update([
                'status' => Quote::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            return $quote->refresh();
        });
    }


    public function reject(Quote $quote): Quote
    {

        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()
                ->lockForUpdate()
                ->findOrFail($quote->id);
            if ($quote->status !== Quote::STATUS_SENT) {
                throw new InvalidArgumentException('Only sent quotes can be rejected.');
            }

            $quote->update([
                'status' => Quote::STATUS_REJECTED,
                'rejected_at' => now(),
            ]);
            return $quote->refresh();
        });
    }


    public function expire(Quote $quote): Quote
    {

        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()
                ->lockForUpdate()
                ->findOrFail($quote->id);
            if ($quote->status !== Quote::STATUS_SENT) {
                throw new InvalidArgumentException('Only sent quotes can expire.');
            }

            if ($quote->valid_until === null || $quote->valid_until->isFuture()) {
                throw new InvalidArgumentException('Quote validity date has not passed.');
            }

            $quote->update([
                'status' => Quote::STATUS_EXPIRED,
            ]);
            return $quote->refresh();
        });
    }
}
