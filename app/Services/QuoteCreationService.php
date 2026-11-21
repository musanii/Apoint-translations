<?php

namespace App\Services;

use App\Models\Quote;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuoteCreationService
{
    /**
     * Create a new class instance.
     */
    public function __construct(private QuoteReferenceGenerator $referenceGenerator)
    {
        //
    }

    public function create(
        QuoteRequest $quoteRequest,
        string $pricingType,
        ?float $quantity,
        ?string $unit,
        ?float $rate,
        float $subtotal,
        float $expressFee = 0,
        float $certificationFee = 0,
        float $courierFee = 0,
        ?string $customerNotes = null,
        ?string $adminNotes = null,

    ): Quote {

        if (
            ! in_array($pricingType, ['per_word', 'per_page', 'fixed'], true) ||
            (
                in_array($pricingType, ['per_word', 'per_page'], true) &&
                ($quantity === null || $unit === null || $rate === null)
            ) ||
            (
                $pricingType === 'fixed' &&
                ($quantity !== null || $unit !== null || $rate !== null)
            ) ||
            $subtotal < 0 ||
            ($rate !== null && $rate < 0) ||
            ($quantity !== null && $quantity <= 0) ||
            $expressFee < 0 ||
            $certificationFee < 0 ||
            $courierFee < 0
        ) {
            throw new InvalidArgumentException(
                'Invalid quote pricing values.'
            );
        }

        return DB::transaction(function () use (

            $quoteRequest,
            $pricingType,
            $quantity,
            $unit,
            $rate,
            $subtotal,
            $expressFee,
            $certificationFee,
            $courierFee,
            $customerNotes,
            $adminNotes,

        ) {
            return Quote::create([
                'quote_request_id' => $quoteRequest->id,
                'currency_id' => $quoteRequest->currency_id,
                'reference' => $this->referenceGenerator->generate(),
                'pricing_type' => $pricingType,
                'quantity' => $quantity,
                'unit' => $unit,
                'rate' => $rate,
                'subtotal' => $subtotal,
                'express_fee' => $expressFee,
                'certification_fee' => $certificationFee,
                'courier_fee' => $courierFee,
                'total' => $subtotal + $expressFee + $certificationFee + $courierFee,
                'customer_notes' => $customerNotes,
                'admin_notes' => $adminNotes,
                'status' => Quote::STATUS_DRAFT,
            ]);
        });
    }
}
