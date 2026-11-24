<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReconciliationController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,successful,failed,refunded'],
            'provider' => ['nullable', 'string', 'in:daraja,payhero,flutterwave'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $payments = Payment::query()->with(['order.user', 'order', 'currency'])
            ->when(($filters['status'] ?? '') !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(($filters['provider'] ?? '') !== '', fn (Builder $query) => $query->where('provider', $filters['provider']))
            ->when(trim($filters['search'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $search = trim($filters['search']);
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('reference', 'like', '%'.$search.'%')
                        ->orWhere('provider_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('order', fn (Builder $order) => $order->where('reference', 'like', '%'.$search.'%')->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')));
                });
            })->latest()->get();

        return response()->streamDownload(function () use ($payments): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Payment reference', 'Order reference', 'Customer', 'Email', 'Provider', 'Method', 'Provider receipt', 'Checkout request ID', 'Amount', 'Currency', 'Payment status', 'Order payment status', 'Created at', 'Paid at']);
            foreach ($payments as $payment) {
                fputcsv($output, [$payment->reference, $payment->order?->reference, $payment->order?->user?->name, $payment->order?->user?->email, $payment->provider, $payment->payment_method, $payment->provider_reference, $payment->provider_checkout_request_id, $payment->amount, $payment->currency?->code, $payment->status, $payment->order?->payment_status, $payment->created_at?->toIso8601String(), $payment->paid_at?->toIso8601String()]);
            }
            fclose($output);
        }, 'apoint-payment-reconciliation-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

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
                    })->orWhere(function (Builder $mismatch): void {
                        $mismatch->where('status', Payment::STATUS_SUCCESSFUL)
                            ->whereHas('order', fn (Builder $order) => $order->where('orders.payment_status', '!=', 'paid'));
                    } )->orWhereHas('refunds', function (Builder $refund): void {
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
                    })->orWhere(function (Builder $mismatch): void {
                        $mismatch->where('status', Payment::STATUS_SUCCESSFUL)
                            ->whereHas('order', fn (Builder $order) => $order->where('orders.payment_status', '!=', 'paid'));
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
