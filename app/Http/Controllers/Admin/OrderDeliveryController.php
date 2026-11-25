<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderDeliveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\File;

class OrderDeliveryController extends Controller
{
    public function store(Request $request, Order $order, OrderDeliveryService $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1', 'max:'.config('apoint.documents.max_files')],
            'documents.*' => [
                'required',
                'file',
                File::types(config('apoint.documents.allowed_extensions'))
                    ->max(config('apoint.documents.max_file_size_kb')),
            ],
        ]);

        $delivery->deliverDigitalOrder($order, $validated['documents'], $request->user());

        return back()->with('success', 'Final documents delivered. The customer has been notified by email and in their account.');
    }
}
