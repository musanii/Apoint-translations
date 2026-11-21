<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentCreationService;
use App\Services\PayHeroStkPushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaymentController extends Controller
{
    public function store(
        Request $request,
        Order $order,
        PaymentCreationService $payments,
        PayHeroStkPushService $payHero
    ): RedirectResponse {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^254[17][0-9]{8}$/'],
        ]);

        if ($order->currency()->value('code') !== 'KES' || floor((float) $order->total) !== (float) $order->total) {
            throw ValidationException::withMessages([
                'payment' => 'Online M-Pesa checkout is available only for whole-number KES amounts.',
            ]);
        }

        if (
            ! filled(config('services.payhero.username')) ||
            ! filled(config('services.payhero.password')) ||
            ! filled(config('services.payhero.callback_url')) ||
            ! filled(config('services.payhero.channel_id'))
        ) {
            throw ValidationException::withMessages([
                'payment' => 'Online checkout is not configured yet. Please contact Apoint to arrange payment.',
            ]);
        }

        try {
            $existingPending = $order->payments()
                ->where('provider', 'payhero')
                ->where('status', Payment::STATUS_PENDING)
                ->latest()
                ->first();

            if ($existingPending?->provider_checkout_request_id !== null) {
                return redirect()->route('orders.show', $order)
                    ->with('success', 'A payment prompt has already been requested. Check your phone before starting another attempt.');
            }

            if ($existingPending?->provider_initiation_started_at !== null) {
                return redirect()->route('orders.show', $order)
                    ->with('error', 'A payment request is still being checked. Please do not retry yet.');
            }

            $payment = $existingPending ?? $payments->createFor(
                $order,
                'payhero',
                'mpesa',
                (string) config('services.payhero.channel_id')
            );

            $payHero->initiate($payment, $data['phone_number']);

            return redirect()->route('orders.show', $order)
                ->with('success', 'M-Pesa prompt requested. Check your phone and approve the payment to complete your order.');
        } catch (Throwable $exception) {
            Log::warning('Customer PayHero checkout initiation did not complete.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('orders.show', $order)
                ->with('error', 'We could not confirm that the payment prompt was sent. Please do not retry immediately; refresh this page or contact Apoint so the payment status can be checked.');
        }
    }
}
