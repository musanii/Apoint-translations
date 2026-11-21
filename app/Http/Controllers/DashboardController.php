<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $visibleQuoteStatuses = [
            Quote::STATUS_SENT,
            Quote::STATUS_ACCEPTED,
            Quote::STATUS_REJECTED,
            Quote::STATUS_EXPIRED,
        ];

        $recentRequests = $user->quoteRequests()
            ->with([
                'service:id,name',
                'currency:id,code,symbol',
                'quotes' => fn ($query) => $query
                    ->whereIn('status', $visibleQuoteStatuses)
                    ->with('currency:id,code,symbol')
                    ->latest(),
            ])
            ->withCount('documents')
            ->latest()
            ->limit(6)
            ->get();

        $recentOrders = $user->orders()
            ->with([
                'currency:id,code,symbol',
                'quote.quoteRequest.service:id,name',
            ])
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('dashboard', [
            'summary' => [
                'requests' => $user->quoteRequests()->count(),
                'quotesAwaitingDecision' => $user->quoteRequests()
                    ->whereHas('quotes', fn (Builder $query) => $query->where('status', Quote::STATUS_SENT))
                    ->count(),
                'activeOrders' => $user->orders()
                    ->whereIn('status', [
                        Order::STATUS_PENDING_PAYMENT,
                        Order::STATUS_CONFIRMED,
                        Order::STATUS_IN_PROGRESS,
                    ])
                    ->count(),
                'completedOrders' => $user->orders()
                    ->where('status', Order::STATUS_COMPLETED)
                    ->count(),
            ],
            'recentRequests' => $recentRequests,
            'recentOrders' => $recentOrders,
        ]);
    }
}
