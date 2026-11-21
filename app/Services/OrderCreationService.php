<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class OrderCreationService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private QuoteStatusService $quoteStatusService,
        private OrderReferenceGenerator $referenceGenerator,
    ) {}

    public function createFromAcceptedQuote(Quote $quote): Order
    {
        return DB::transaction(function () use ($quote) {
            $lockedQuote = Quote::query()
                ->lockForUpdate()
                ->findOrFail($quote->id);

            $this->quoteStatusService->accept($lockedQuote);
            $quoteRequest = $lockedQuote->quoteRequest;

            return Order::create([
                'quote_id' => $lockedQuote->id,
                'user_id' => $quoteRequest->user_id,
                'currency_id' => $lockedQuote->currency_id,
                'reference' => $this->referenceGenerator->generate(),
                'subtotal' => $lockedQuote->subtotal,
                'express_fee' => $lockedQuote->express_fee,
                'certification_fee' => $lockedQuote->certification_fee,
                'courier_fee' => $lockedQuote->courier_fee,
                'total' => $lockedQuote->total,
                'delivery_method' => $quoteRequest->delivery_method,
                'recipient_name' => $quoteRequest->recipient_name,
                'recipient_phone' => $quoteRequest->recipient_phone,
                'delivery_country' => $quoteRequest->delivery_country,
                'delivery_address' => $quoteRequest->delivery_address,
                'delivery_postal_code' => $quoteRequest->delivery_postal_code,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,

            ]);
        });
    }
}
