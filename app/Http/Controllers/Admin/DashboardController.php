<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Models\Order;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard', [
            'counts' => [
                'totalRequests' => QuoteRequest::count(),
                'pendingRequests' => QuoteRequest::where('status', 'pending')->count(),
                'underReview' => QuoteRequest::where('status', 'under_review')->count(),
                'draftQuotes' => QuoteRequest::where('status', 'quote_draft')->count(),
                'sentQuotes' => QuoteRequest::where('status', 'quote_sent')->count(),
                'orders' => Order::count(),
            ],
        ]);
    }
}