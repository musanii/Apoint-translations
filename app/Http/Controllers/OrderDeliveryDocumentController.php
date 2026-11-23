<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderDeliveryDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderDeliveryDocumentController extends Controller
{
    public function download(Request $request, Order $order, OrderDeliveryDocument $document): StreamedResponse
    {
        $this->authorizeCustomerDocument($request, $order, $document);

        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download($document->path, $document->original_name);
    }

    public function preview(Request $request, Order $order, OrderDeliveryDocument $document): StreamedResponse
    {
        $this->authorizeCustomerDocument($request, $order, $document);

        abort_unless(in_array($document->mime_type, [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ], true), 415, 'Preview is available for PDF, JPEG and PNG files. Download this file to view it.');

        $disk = Storage::disk($document->disk);
        abort_unless($disk->exists($document->path), 404);

        return $disk->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }

    private function authorizeCustomerDocument(Request $request, Order $order, OrderDeliveryDocument $document): void
    {
        abort_unless($order->user_id === $request->user()->id, 404);
        abort_unless($document->order_id === $order->id, 404);
        abort_unless($order->status === Order::STATUS_COMPLETED, 404);
    }
}
