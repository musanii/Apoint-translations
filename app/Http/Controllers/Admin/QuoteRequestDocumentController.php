<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequestDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteRequestDocumentController extends Controller
{
    public function download(QuoteRequestDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk($document->disk)->exists($document->path), 404);

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name,
        );
    }

    public function preview(QuoteRequestDocument $document): StreamedResponse
    {
        abort_unless(
            in_array($document->mime_type, [
                'application/pdf',
                'image/jpeg',
                'image/png',
            ], true),
            415,
            'Preview is available for PDF, JPEG and PNG documents only.',
        );

        $disk = Storage::disk($document->disk);

        abort_unless($disk->exists($document->path), 404);

        return $disk->response(
            $document->path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }
}
