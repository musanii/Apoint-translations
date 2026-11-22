<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MpesaStkPushService;
use App\Services\PaymentCreationService;
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
        MpesaStkPushService $daraja
    ): RedirectResponse {
        abort_unless($order->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'phone_number' => ['required', 'string', 'regex:/^254[17][0-9]{8}$/'],
        ]);

        if ($order->currency()->value('code') !== 'KES' || floor((float) $order->total) !== (float) $order->total) {
            throw ValidationException::withMessages([
                'payment' => 'M-Pesa checkout is available only for whole-number KES amounts.',
            ]);
        }

        if (
            ! filled(config('services.mpesa.consumer_key')) ||
            ! filled(config('services.mpesa.consumer_secret')) ||
            ! filled(config('services.mpesa.shortcode')) ||
            ! filled(config('services.mpesa.passkey')) ||
            ! filled(config('services.mpesa.callback_url'))
        ) {
            throw ValidationException::withMessages([
                'payment' => 'Daraja M-Pesa checkout is not configured yet. Please contact Apoint to arrange payment.',
            ]);
        }

        try {
            $existingPending = $order->payments()
                ->where('provider', 'daraja')
                ->where('status', Payment::STATUS_PENDING)
                ->latest()
                ->first();

            if ($existingPending?->provider_checkout_request_id !== null) {
                return redirect()->route('orders.show', $order)
                    ->with('success', 'An M-Pesa prompt has already been requested. Check your phone before starting another attempt.');
            }

            if ($existingPending?->provider_initiation_started_at !== null) {
                return redirect()->route('orders.show', $order)
                    ->with('error', 'Your payment request is still being checked. Please do not retry yet.');
            }

            $payment = $existingPending ?? $payments->createFor(
                $order,
                'daraja',
                'mpesa'
            );

            $daraja->initiate($payment, $data['phone_number']);

            return redirect()->route('orders.show', $order)
                ->with('success', 'M-Pesa prompt requested. Check your phone and approve the payment to complete your order.');
        } catch (Throwable $exception) {
            Log::warning('Customer Daraja checkout initiation did not complete.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->route('orders.show', $order)
                ->with('success', 'We are checking your M-Pesa request. Keep this page open and check your phone. Do not start another payment while we verify its status.');
        }
    }
}
