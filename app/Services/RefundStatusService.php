<?php

namespace App\Services;

use App\Models\Refund;
use InvalidArgumentException;

class RefundStatusService
{
    public function markProcessing(Refund $refund): Refund
    {
        if ($refund->status !== Refund::STATUS_PENDING) {
            throw new InvalidArgumentException(
                'Only pending refunds can be marked as processing.'
            );
        }

        $refund->update([
            'status' => Refund::STATUS_PROCESSING,
        ]);

        return $refund->refresh();
    }

    public function markSuccessful(
        Refund $refund,
        ?string $providerReference = null
    ): Refund {
        if (! in_array($refund->status, [
            Refund::STATUS_PENDING,
            Refund::STATUS_PROCESSING,
        ], true)) {
            throw new InvalidArgumentException(
                'Only pending or processing refunds can be marked as successful.'
            );
        }

        $refund->update([
            'status' => Refund::STATUS_SUCCESSFUL,
            'provider_reference' => $providerReference,
            'completed_at' => now(),
            'failure_reason' => null,
        ]);

        return $refund->refresh();
    }

    public function markFailed(
        Refund $refund,
        string $failureReason
    ): Refund {
        if ($refund->status !== Refund::STATUS_PROCESSING) {
            throw new InvalidArgumentException(
                'Only processing refunds can be marked as failed.'
            );
        }

        $refund->update([
            'status' => Refund::STATUS_FAILED,
            'failure_reason' => $failureReason,
            'completed_at' => now(),
        ]);

        return $refund->refresh();
    }
}
