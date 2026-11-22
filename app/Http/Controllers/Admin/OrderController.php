<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:pending_payment,confirmed,in_progress,completed,cancelled'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $status = $filters['status'] ?? '';
        $search = trim($filters['search'] ?? '');

        $orders = Order::query()
            ->with([
                'user:id,name,email',
                'currency:id,code',
                'quote.quoteRequest.service:id,name',
            ])
            ->when(in_array($status, [
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_CONFIRMED,
                Order::STATUS_IN_PROGRESS,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ], true), fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Order $order): array => [
                'id' => $order->id,
                'reference' => $order->reference,
                'customer' => $order->user->name,
                'email' => $order->user->email,
                'service' => $order->quote->quoteRequest->service->name,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => $order->total,
                'currency' => $order->currency->code,
                'created_at' => $order->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/orders/index', [
            'orders' => $orders,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function startProcessing(Order $order, OrderStatusService $service): RedirectResponse
    {
        return $this->transition(fn () => $service->startProcessing($order), 'Order moved into processing.');
    }

    public function complete(Order $order, OrderStatusService $service): RedirectResponse
    {
        return $this->transition(fn () => $service->complete($order), 'Order marked as completed.');
    }

    public function cancel(Order $order, OrderStatusService $service): RedirectResponse
    {
        return $this->transition(fn () => $service->cancel($order), 'Order cancelled.');
    }

    private function transition(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['order' => $exception->getMessage()]);
        }

        return back()->with('success', $success);
    }
}
