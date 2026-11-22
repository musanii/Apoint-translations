<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateQuoteDraftRequest;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Services\QuoteCreationService;
use App\Services\QuoteStatusService;
use Illuminate\Http\RedirectResponse;

class QuoteDraftController extends Controller
{
    public function store(CreateQuoteDraftRequest $request, QuoteRequest $quoteRequest, QuoteCreationService $service): RedirectResponse
    {
        $data = $request->validated();
        $fixed = $data['pricing_type'] === 'fixed';
        $quantity = $fixed ? null : (float) $data['quantity'];
        $unit = $fixed ? null : $data['unit'];
        $rate = $fixed ? null : (float) $data['rate'];
        $subtotal = $fixed ? (float) $data['subtotal'] : round($quantity * $rate, 2);

        $service->create(
            quoteRequest: $quoteRequest,
            pricingType: $data['pricing_type'],
            quantity: $quantity,
            unit: $unit,
            rate: $rate,
            subtotal: $subtotal,
            expressFee: (float) ($data['express_fee'] ?? 0),
            certificationFee: (float) ($data['certification_fee'] ?? 0),
            courierFee: (float) ($data['courier_fee'] ?? 0),
            customerNotes: $data['customer_notes'] ?? null,
            adminNotes: $data['admin_notes'] ?? null,
        );

        $quoteRequest->update(['status' => 'quote_draft']);

        return back()->with('success', 'Quotation draft saved. It has not been sent to the customer.');
    }

    public function send(Quote $quote, QuoteStatusService $service, \\App\\Services\\NotificationService $notifications): RedirectResponse
    {
        $quote->load('quoteRequest');

        if ($quote->status !== Quote::STATUS_DRAFT) {
            return back()->withErrors(['quote' => 'Only draft quotations can be sent.']);
        }

        $service->send($quote, now()->addDays(7));
        $quote->quoteRequest->update(['status' => 'quote_sent']);
        $customer = $quote->quoteRequest->user;
        $notifications->user($customer, 'Your quotation is ready', 'Quotation '.$quote->reference.' is ready for your review. It is valid for 7 days.', route('quotes.show', $quote), 'quote', true);

        return back()->with('success', 'Quotation sent to the customer dashboard. It is valid for 7 days.');
    }
}
