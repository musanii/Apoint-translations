<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequestDocumentRequest;
use App\Models\QuoteRequest;
use App\Services\QuoteRequestDocumentService;
use Illuminate\Http\RedirectResponse;

class QuoteRequestDocumentController extends Controller
{
    public function store(
        StoreQuoteRequestDocumentRequest $request,
        QuoteRequest $quoteRequest,
        QuoteRequestDocumentService $documentService,
        \\App\\Services\\NotificationService $notifications
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('view', $quoteRequest),
            403
        );

        $documentService->upload($quoteRequest, $request->file('document'));
        $notifications->admins('New document uploaded', 'A client uploaded a document for quote request #'.$quoteRequest->id.'.', route('admin.quote-requests.show', $quoteRequest), 'document');

        return back()->with(
            'success',
            'Document uploaded successfully.'
        );
    }
}
