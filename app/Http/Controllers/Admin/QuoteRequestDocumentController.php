<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\QuoteRequestDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
class QuoteRequestDocumentController extends Controller
{
    public function download(QuoteRequestDocument $document): StreamedResponse {
        abort_unless(Storage::disk($document->disk)->exists($document->path),404);
        return Storage::disk($document->disk)->download($document->path,$document->original_name);
    }
}
