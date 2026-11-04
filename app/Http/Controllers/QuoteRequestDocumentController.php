<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequestDocumentRequest;
use App\Models\QuoteRequest;
use App\Services\QuoteRequestDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteRequestDocumentController extends Controller
{
    public function store(
        StoreQuoteRequestDocumentRequest $request,
        QuoteRequest $quoteRequest,
        QuoteRequestDocumentService $documentService
    ): RedirectResponse {
        abort_unless(
            $request->user()->can('view', $quoteRequest),
            403
        );

        $documentService->upload($quoteRequest, $request->file('document'));
        return back()->with(
            'success',
            'Document uploaded successfully.'
        );
    }
}
