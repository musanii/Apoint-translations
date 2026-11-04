<?php

namespace App\Services;

use App\Models\QuoteRequest;
use App\Models\QuoteRequestDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class QuoteRequestDocumentService
{
    public function upload(
        QuoteRequest $quoteRequest,
        UploadedFile $file
    ): QuoteRequestDocument {
        // 1. Enforce the maximum number of documents per request.
        $maxFiles = (int) config('apoint.documents.max_files');

        if ($quoteRequest->documents()->count() >= $maxFiles) {
            throw ValidationException::withMessages([
                'document' => 'You have reached the maximum number of documents.',
            ]);
        }

        // 2. Enforce the maximum file size.
        $maxFileSizeBytes = (int) config('apoint.documents.max_file_size_kb') * 1024;

        if ($file->getSize() > $maxFileSizeBytes) {
            throw ValidationException::withMessages([
                'document' => 'Each document must not exceed 10 MB.',
            ]);
        }

        // 3. Check the extension against our configured allowlist.
        $extension = strtolower($file->getClientOriginalExtension());

        $allowedExtensions = config('apoint.documents.allowed_extensions', []);

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'document' => 'This file type is not supported.',
            ]);
        }

        // 4. Check the actual MIME type detected from the uploaded file.
        // Do not rely on the MIME type supplied by the browser.
        $mimeType = $file->getMimeType();

        $allowedMimeTypes = [
            'pdf'  => ['application/pdf'],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
            ],
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'xlsx' => [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/zip',
            ],
            'csv'  => [
                'text/plain',
                'text/csv',
                'application/csv',
                'application/vnd.ms-excel',
            ],
            'pptx' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
            ],
            'zip'  => [
                'application/zip',
                'application/x-zip',
                'application/x-zip-compressed',
            ],
        ];

        if (
            ! isset($allowedMimeTypes[$extension]) ||
            ! in_array($mimeType, $allowedMimeTypes[$extension], true)
        ) {
            throw ValidationException::withMessages([
                'document' => 'The uploaded file content does not match its file type.',
            ]);
        }

        // 5. Store the file privately using Laravel's generated filename.
        $disk = 'local';
        $directory = "quote-requests/{$quoteRequest->id}";

        $storedPath = $file->store($directory, $disk);

        if (! $storedPath) {
            throw new \RuntimeException('The document could not be stored.');
        }

        // 6. Save the document record.
        // If the database operation fails, remove the stored file.
        try {
            return DB::transaction(function () use (
                $quoteRequest,
                $file,
                $extension,
                $mimeType,
                $disk,
                $storedPath
            ) {
                return $quoteRequest->documents()->create([
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => basename($storedPath),
                    'disk' => $disk,
                    'path' => $storedPath,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size' => $file->getSize(),
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);

            throw $exception;
        }
    }
}
