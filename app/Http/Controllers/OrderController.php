<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        $order->load([
            'currency:id,code,symbol',
            'quote.currency:id,code,symbol',
            'quote.quoteRequest.service:id,name',
            'payments' => fn ($query) => $query->latest()->limit(1),
        ]);

        $latestPayment = $order->payments->first();
        $darajaConfigured = filled(config('services.mpesa.consumer_key'))
            && filled(config('services.mpesa.consumer_secret'))
            && filled(config('services.mpesa.shortcode'))
            && filled(config('services.mpesa.passkey'))
            && filled(config('services.mpesa.callback_url'));

        $canPayOnline = $darajaConfigured
            && $order->currency->code === 'KES'
            && floor((float) $order->total) === (float) $order->total
            && $order->status === Order::STATUS_PENDING_PAYMENT
            && in_array($order->payment_status, [
                Order::PAYMENT_STATUS_UNPAID,
                Order::PAYMENT_STATUS_FAILED,
                Order::PAYMENT_STATUS_PENDING,
            ], true);

        return Inertia::render('orders/show', [
            'order' => [
                'id' => $order->id,
                'reference' => $order->reference,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'subtotal' => $order->subtotal,
                'express_fee' => $order->express_fee,
                'certification_fee' => $order->certification_fee,
                'courier_fee' => $order->courier_fee,
                'total' => $order->total,
                'delivery_method' => $order->delivery_method,
                'currency' => $order->currency,
                'service' => $order->quote->quoteRequest->service->name,
                'quote_reference' => $order->quote->reference,
                'latest_payment' => $latestPayment ? [
                    'status' => $latestPayment->status,
                    'provider_checkout_request_id' => $latestPayment->provider_checkout_request_id,
                    'failure_reason' => $latestPayment->failure_reason,
                ] : null,
            ],
            'canPayOnline' => $canPayOnline,
        ]);
    }
}
