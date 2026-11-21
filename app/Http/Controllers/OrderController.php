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
        ]);

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
            ],
        ]);
    }
}
