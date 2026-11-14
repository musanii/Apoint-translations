<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RefundCreationService
{
    public function __construct(
        private readonly RefundReferenceGenerator $referenceGenerator
    ) {}

    public function create(
        Payment $payment,
        string $amount,
        ?string $reason = null
    ): Refund {
        $refundAmount = $this->toMinorUnits($amount);

        if ($refundAmount <= 0) {
            throw new InvalidArgumentException(
                'Refund amount must be greater than zero.'
            );
        }

        return DB::transaction(function () use (
            $payment,
            $refundAmount,
            $reason
        ) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->findOrFail($payment->id);

            if ($payment->status !== Payment::STATUS_SUCCESSFUL) {
                throw new InvalidArgumentException(
                    'Only successful payments can be refunded.'
                );
            }

            $paymentAmount = $this->toMinorUnits(
                (string) $payment->amount
            );

            $reservedAmount = $payment->refunds()
                ->whereIn('status', [
                    Refund::STATUS_PENDING,
                    Refund::STATUS_PROCESSING,
                    Refund::STATUS_SUCCESSFUL,
                ])
                ->get()
                ->sum(fn (Refund $refund) => $this->toMinorUnits(
                    (string) $refund->amount
                ));

            if ($reservedAmount + $refundAmount > $paymentAmount) {
                throw new InvalidArgumentException(
                    'Refund amount exceeds the remaining refundable balance.'
                );
            }

            return $payment->refunds()->create([
                'reference' => $this->referenceGenerator->generate(),
                'amount' => number_format($refundAmount / 100, 2, '.', ''),
                'status' => Refund::STATUS_PENDING,
                'reason' => $reason,
                'initiated_at' => now(),
            ]);
        });
    }

    private function toMinorUnits(string $amount): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException(
                'Amount must be a valid monetary value with up to two decimal places.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
