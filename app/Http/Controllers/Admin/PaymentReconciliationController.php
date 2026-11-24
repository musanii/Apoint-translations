<?php

namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;
use App\\Models\\Payment;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Carbon;
use Inertia\\Inertia;
use Inertia\\Response;

class PaymentReconciliationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,successful,failed,refunded'],
            'provider' => ['nullable', 'string', 'in:daraja,payhero,flutterwave'],
            'search' => ['nullable', 'string', 'max:120'],
            'attention' => ['nullable', 'boolean'],
        ]);

        $status = $filters['status'] ?? '';
        $provider = $filters['provider'] ?? '';
        $search = trim($filters['search'] ?? '');
        $attentionOnly = (bool) ($filters['attention'] ?? false);
        $staleBefore = now()->subMinutes(10);

        $payments = Payment::query()
            ->with([
                'order.user:id,name,email',
                'order:id,reference,payment_status',
                'currency:id,code',
                'refunds:id,payment_id,reference,amount,status',
            ])
            ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
            ->when($provider !== '', fn (Builder $query) => $query->where('provider', $provider))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('provider_reference', 'like', '%'.$search.'%')
                        ->orWhere('provider_checkout_request_id', 'like', '%'.$search.'%')
                        ->orWhereHas('order', function (Builder $order) use ($search): void {
                            $order->where('reference', 'like', '%'.$search.'%')
                                ->orWhereHas('user', function (Builder $user) use ($search): void {
                                    $user->where('name', 'like', '%'.$search.'%')
                                        ->orWhere('email', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->when($attentionOnly, function (Builder $query) use ($staleBefore): void {
                $query->where(function (Builder $attention) use ($staleBefore): void {
                    $attention->where(function (Builder $pending) use ($staleBefore): void {
                        $pending->where('status', Payment::STATUS_PENDING)
                            ->where(function (Builder $stale) use ($staleBefore): void {
                                $stale->where('created_at', '<=', $staleBefore)
                                    ->orWhere('reconciliation_attempts', '>=', 6);
                            });
                    })->orWhere(function (Builder $successful): void {
                        $successful->where('status', Payment::STATUS_SUCCESSFUL)
                            ->whereNull('provider_reference');
                    })->orWhereHas('order', function (Builder $order): void {
                        $order->whereColumn('orders.payment_status', '!=', 'payments.status')
                            ->where('payments.status', Payment::STATUS_SUCCESSFUL);
                    })->orWhereHas('refunds', function (Builder $refund): void {
                        $refund->whereIn('status', ['pending', 'processing']);
                    });
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(function (Payment $payment) use ($staleBefore): array {
                $attentionReason = null;

                if ($payment->status === Payment::STATUS_PENDING && $payment->reconciliation_attempts >= 6) {
                    $attentionReason = 'Automatic checks exhausted';
                } elseif ($payment->status === Payment::STATUS_PENDING && $payment->created_at?->lte($staleBefore)) {
                    $attentionReason = 'Awaiting provider confirmation';
                } elseif ($payment->status === Payment::STATUS_SUCCESSFUL && $payment->provider_reference === null) {
                    $attentionReason = 'Provider receipt reference not yet attached';
                } elseif ($payment->status === Payment::STATUS_SUCCESSFUL && $payment->order?->payment_status !== 'paid') {
                    $attentionReason = 'Payment and order statuses do not match';
                } elseif ($payment->refunds->contains(fn ($refund) => in_array($refund->status, ['pending', 'processing'], true))) {
                    $attentionReason = 'Refund awaiting resolution';
                }

                return [
                    'id' => $payment->id,
                    'reference' => $payment->reference,
                    'order_reference' => $payment->order?->reference,
                    'customer' => $payment->order?->user?->name ?? 'Unknown customer',
                    'customer_email' => $payment->order?->user?->email,
                    'provider' => $payment->provider,
                    'payment_method' => $payment->payment_method,
                    'provider_reference' => $payment->provider_reference,
                    'checkout_request_id' => $payment->provider_checkout_request_id,
                    'amount' => (string) $payment->amount,
                    'currency' => $payment->currency?->code ?? '',
                    'status' => $payment->status,
                    'order_payment_status' => $payment->order?->payment_status,
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                    'created_at' => $payment->created_at?->toIso8601String(),
                    'reconciliation_attempts' => $payment->reconciliation_attempts,
                    'last_reconciled_at' => $payment->last_reconciled_at?->toIso8601String(),
                    'next_reconciliation_at' => $payment->next_reconciliation_at?->toIso8601String(),
                    'attention_reason' => $attentionReason,
                    'refunds' => $payment->refunds->map(fn ($refund) => [
                        'reference' => $refund->reference,
                        'amount' => (string) $refund->amount,
                        'status' => $refund->status,
                    ])->values(),
                ];
            });

        $stats = [
            'total' => Payment::count(),
            'pending' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'successful' => Payment::where('status', Payment::STATUS_SUCCESSFUL)->count(),
            'failed' => Payment::where('status', Payment::STATUS_FAILED)->count(),
            'attention' => Payment::query()
                ->where(function (Builder $query) use ($staleBefore): void {
                    $query->where(function (Builder $pending) use ($staleBefore): void {
                        $pending->where('status', Payment::STATUS_PENDING)
                            ->where(function (Builder $stale) use ($staleBefore): void {
                                $stale->where('created_at', '<=', $staleBefore)
                                    ->orWhere('reconciliation_attempts', '>=', 6);
                            });
                    })->orWhere(function (Builder $successful): void {
                        $successful->where('status', Payment::STATUS_SUCCESSFUL)
                            ->whereNull('provider_reference');
                    })->orWhereHas('order', function (Builder $order): void {
                        $order->whereColumn('orders.payment_status', '!=', 'payments.status')
                            ->where('payments.status', Payment::STATUS_SUCCESSFUL);
                    })->orWhereHas('refunds', function (Builder $refund): void {
                        $refund->whereIn('status', ['pending', 'processing']);
                    });
                })->count(),
        ];

        return Inertia::render('admin/payments/reconciliation', [
            'payments' => $payments,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'provider' => $provider,
                'search' => $search,
                'attention' => $attentionOnly,
            ],
        ]);
    }
}
