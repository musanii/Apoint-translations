<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentReconciliationEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MpesaPaymentReconciliationRunner
{
    private const MAX_ATTEMPTS = 6;

    private const RETRY_DELAYS = [
        2,
        5,
        15,
        30,
        60,
    ];

    public function __construct(private readonly MpesaPaymentReconciliationService $reconciliationService) {}

    private function claimAttempt(int $paymentId): ?Payment
    {
        return DB::transaction(function () use ($paymentId) {
            $payment = Payment::query()
                ->lockForUpdate()
                ->find($paymentId);

            if (
                ! $payment ||
                $payment->provider !== 'daraja' ||
                $payment->payment_method !== 'mpesa' ||
                $payment->status !== Payment::STATUS_PENDING ||
                ! $payment->provider_checkout_request_id ||
                $payment->reconciliation_attempts >= self::MAX_ATTEMPTS ||
                (
                    $payment->next_reconciliation_at !== null &&
                    $payment->next_reconciliation_at->isFuture()
                )

            ) {
                return null;
            }

            $attempt = $payment->reconciliation_attempts + 1;
            $delay = self::RETRY_DELAYS[$attempt - 1] ?? null;

            $payment->update([
                'reconciliation_attempts' => $attempt,
                'last_reconciled_at' => now(),
                'next_reconciliation_at' => $delay !== null
                    ? now()->addMinutes($delay)
                    : null,

            ]);

            return $payment->refresh();
        });
    }

    public function run(int $limit = 50): int
    {
        if ($limit < 1) {
            throw new \InvalidArgumentException(
                'The reconciliation batch limit must be greater than zero.'
            );
        }
        $paymentIds = Payment::query()
            ->where('provider', 'daraja')
            ->where('payment_method', 'mpesa')
            ->where('status', Payment::STATUS_PENDING)
            ->whereNotNull('provider_checkout_request_id')
            ->where('reconciliation_attempts', '<', self::MAX_ATTEMPTS)
            ->where(function ($query) {
                $query->whereNull('next_reconciliation_at')
                    ->orWhere('next_reconciliation_at', '<=', now());
            })
            ->orderBy('next_reconciliation_at')
            ->limit($limit)
            ->pluck('id');

        $processed = 0;
        foreach ($paymentIds as $paymentId) {
            $payment = $this->claimAttempt($paymentId);

            if (! $payment) {
                continue;
            }
            $processed++;
            try {
                $result = $this->reconciliationService->reconcile($payment);
                PaymentReconciliationEvent::query()->create([
                    'payment_id' => $payment->id,
                    'event_type' => 'automatic_check',
                    'outcome' => $result->status === Payment::STATUS_PENDING ? 'pending' : $result->status,
                    'message' => $result->status === Payment::STATUS_PENDING
                        ? 'Daraja has not reported a final payment result yet.'
                        : 'Daraja payment status checked successfully.',
                    'attempt_number' => $payment->reconciliation_attempts,
                ]);
            } catch (\Throwable $exception) {
                PaymentReconciliationEvent::query()->create([
                    'payment_id' => $payment->id,
                    'event_type' => 'automatic_check',
                    'outcome' => 'error',
                    'message' => 'Provider verification could not be completed. See application logs for technical details.',
                    'attempt_number' => $payment->reconciliation_attempts,
                ]);
                Log::error('M-Pesa payment reconciliation failed.', [
                    'payment_id' => $payment->id,
                    'attempt' => $payment->reconciliation_attempts,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        return $processed;
    }
}
