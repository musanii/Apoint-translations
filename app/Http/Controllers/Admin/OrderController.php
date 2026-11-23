<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use stdClass;

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

        $orders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->join('quotes', 'orders.quote_id', '=', 'quotes.id')
            ->join('quote_requests', 'quotes.quote_request_id', '=', 'quote_requests.id')
            ->join('services', 'quote_requests.service_id', '=', 'services.id')
            ->join('currencies', 'orders.currency_id', '=', 'currencies.id')
            ->select([
                'orders.id',
                'orders.reference',
                'users.name as customer',
                'users.email',
                'services.name as service',
                'orders.status',
                'orders.delivery_method',
                'orders.payment_status',
                'orders.total',
                'currencies.code as currency',
                'orders.created_at',
            ])
            ->when(
                $status !== '',
                fn (Builder $query) => $query->where('orders.status', $status),
            )
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('orders.reference', 'like', '%'.$search.'%')
                        ->orWhere('users.name', 'like', '%'.$search.'%')
                        ->orWhere('users.email', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('orders.created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function (stdClass $order): array {
                return [
                    'id' => (int) $order->id,
                    'reference' => (string) $order->reference,
                    'customer' => (string) $order->customer,
                    'email' => (string) $order->email,
                    'service' => (string) $order->service,
                    'status' => (string) $order->status,
                    'delivery_method' => (string) $order->delivery_method,
                    'payment_status' => (string) $order->payment_status,
                    'total' => (string) $order->total,
                    'currency' => (string) $order->currency,
                    'created_at' => is_string($order->created_at)
                        ? Carbon::parse($order->created_at)->toIso8601String()
                        : null,
                ];
            });

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
        if ($order->delivery_method === 'digital') {
            throw ValidationException::withMessages(['order' => 'Upload the final documents to complete an online-delivery order.']);
        }

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
