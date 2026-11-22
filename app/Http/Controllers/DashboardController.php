<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if ($request->user()->is_admin) {
            return redirect()->route('admin.dashboard');
        }

        $user = $request->user();
        $visibleQuoteStatuses = [
            \App\Models\Quote::STATUS_SENT,
            \App\Models\Quote::STATUS_ACCEPTED,
            \App\Models\Quote::STATUS_REJECTED,
            \App\Models\Quote::STATUS_EXPIRED,
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
                    ->whereHas('quotes', fn ($query) => $query->where('status', \App\Models\Quote::STATUS_SENT))
                    ->count(),
                'activeOrders' => $user->orders()
                    ->whereIn('status', [
                        \App\Models\Order::STATUS_PENDING_PAYMENT,
                        \App\Models\Order::STATUS_CONFIRMED,
                        \App\Models\Order::STATUS_IN_PROGRESS,
                    ])
                    ->count(),
                'completedOrders' => $user->orders()
                    ->where('status', \App\Models\Order::STATUS_COMPLETED)
                    ->count(),
            ],
            'recentRequests' => $recentRequests,
            'recentOrders' => $recentOrders,
        ]);
    }
}