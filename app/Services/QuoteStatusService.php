<?php

namespace App\Services;

use App\Models\Quote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuoteStatusService
{
    public function send(Quote $quote, ?Carbon $validUntil = null): Quote
    {
        return DB::transaction(function () use ($quote, $validUntil) {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            if ($quote->status !== Quote::STATUS_DRAFT) {
                throw new InvalidArgumentException('Only draft quotes can be sent.');
            }
            $quote->update([
                'status' => Quote::STATUS_SENT,
                'sent_at' => now(),
                'valid_until' => $validUntil ?? $quote->valid_until,
            ]);
            return $quote->refresh();
        });
    }

    public function accept(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            if ($quote->status !== Quote::STATUS_SENT) {
                throw new InvalidArgumentException('Only sent quotes can be accepted.');
            }
            if ($quote->valid_until !== null && $quote->valid_until->isPast()) {
                throw new InvalidArgumentException('This quotation has expired.');
            }
            $quote->update(['status' => Quote::STATUS_ACCEPTED, 'accepted_at' => now()]);
            return $quote->refresh();
        });
    }

    public function reject(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            if ($quote->status !== Quote::STATUS_SENT) {
                throw new InvalidArgumentException('Only sent quotes can be rejected.');
            }
            if ($quote->valid_until !== null && $quote->valid_until->isPast()) {
                throw new InvalidArgumentException('This quotation has expired.');
            }
            $quote->update(['status' => Quote::STATUS_REJECTED, 'rejected_at' => now()]);
            return $quote->refresh();
        });
    }

    public function expire(Quote $quote): Quote
    {
        return DB::transaction(function () use ($quote) {
            $quote = Quote::query()->lockForUpdate()->findOrFail($quote->id);
            if ($quote->status !== Quote::STATUS_SENT) {
                throw new InvalidArgumentException('Only sent quotes can expire.');
            }
            if ($quote->valid_until === null || $quote->valid_until->isFuture()) {
                throw new InvalidArgumentException('Quote validity date has not passed.');
            }
            $quote->update(['status' => Quote::STATUS_EXPIRED]);
            return $quote->refresh();
        });
    }
}
