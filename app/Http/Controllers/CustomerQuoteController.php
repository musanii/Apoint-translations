<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Services\OrderCreationService;
use App\Services\QuoteStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerQuoteController extends Controller
{
    public function show(Request $request, Quote $quote, QuoteStatusService $statusService): Response|RedirectResponse
    {
        $quote->load(['quoteRequest.service:id,name', 'quoteRequest.sourceLanguage:id,name', 'quoteRequest.targetLanguage:id,name', 'currency:id,code,symbol']);

        abort_unless($quote->quoteRequest->user_id === $request->user()->id, 404);
        abort_unless($quote->status !== Quote::STATUS_DRAFT, 404);

        if ($quote->status === Quote::STATUS_SENT && $quote->valid_until?->isPast()) {
            $statusService->expire($quote);

            return redirect()->route('dashboard')->with('error', 'That quotation has expired. Please contact Apoint if you would like an updated quote.');
        }

        return Inertia::render('quotes/show', [
            'quote' => [
                'id' => $quote->id,
                'reference' => $quote->reference,
                'pricing_type' => $quote->pricing_type,
                'quantity' => $quote->quantity,
                'unit' => $quote->unit,
                'rate' => $quote->rate,
                'subtotal' => $quote->subtotal,
                'express_fee' => $quote->express_fee,
                'certification_fee' => $quote->certification_fee,
                'courier_fee' => $quote->courier_fee,
                'total' => $quote->total,
                'customer_notes' => $quote->customer_notes,
                'status' => $quote->status,
                'sent_at' => $quote->sent_at,
                'valid_until' => $quote->valid_until,
                'currency' => $quote->currency,
                'request' => [
                    'id' => $quote->quoteRequest->id,
                    'service' => $quote->quoteRequest->service->name,
                    'source_language' => $quote->quoteRequest->sourceLanguage?->name,
                    'target_language' => $quote->quoteRequest->targetLanguage?->name,
                ],
            ],
        ]);
    }

    public function accept(Request $request, Quote $quote, OrderCreationService $orderService, \App\Services\NotificationService $notifications): RedirectResponse
    {
        abort_unless($quote->quoteRequest()->where('user_id', $request->user()->id)->exists(), 404);

        if ($quote->status !== Quote::STATUS_SENT) {
            return redirect()->route('dashboard')->with('error', 'This quotation is no longer available for acceptance.');
        }

        if ($quote->valid_until !== null && $quote->valid_until->isPast()) {
            app(QuoteStatusService::class)->expire($quote);
            return redirect()->route('dashboard')->with('error', 'This quotation has expired. Please contact Apoint for an updated quotation.');
        }

        $order = $orderService->createFromAcceptedQuote($quote);
        $notifications->admins('Quotation accepted', 'A client accepted quotation '.$quote->reference.'. Order '.$order->reference.' is awaiting payment.', route('admin.quote-requests.show', $quote->quoteRequest), 'order', true);
        $notifications->user($request->user(), 'Quotation accepted', 'Your order '.$order->reference.' has been created and is awaiting payment.', route('orders.show', $order), 'order');

        return redirect()->route('orders.show', $order)->with('success', 'Quotation accepted. Your order has been created and is awaiting payment.');
    }

    public function reject(Request $request, Quote $quote, QuoteStatusService $statusService, \App\Services\NotificationService $notifications): RedirectResponse
    {
        abort_unless($quote->quoteRequest()->where('user_id', $request->user()->id)->exists(), 404);

        if ($quote->status !== Quote::STATUS_SENT) {
            return redirect()->route('dashboard')->with('error', 'This quotation is no longer available for a decision.');
        }

        if ($quote->valid_until !== null && $quote->valid_until->isPast()) {
            $statusService->expire($quote);
            return redirect()->route('dashboard')->with('error', 'This quotation has expired. Please contact Apoint for an updated quotation.');
        }

        $statusService->reject($quote);
        $notifications->admins('Quotation declined', 'A client declined quotation '.$quote->reference.'.', route('admin.quote-requests.show', $quote->quoteRequest), 'quote', true);
        $notifications->user($request->user(), 'Quotation declined', 'You declined quotation '.$quote->reference.'. Contact Apoint if you would like to discuss it.', route('dashboard'), 'quote');

        return redirect()->route('dashboard')->with('success', 'You declined the quotation. You can contact Apoint if you would like to discuss it.');
    }
}
