<?php

use App\Models\Quote;
use App\Models\QuoteRequestDocument;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function makeDocumentAdmin(): User
{
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    return $admin;
}

function makeQuoteDocument(string $extension, string $mimeType, string $path): QuoteRequestDocument
{
    $quote = Quote::factory()->create();
    $document = $quote->quoteRequest->documents()->create([
        'original_name' => 'customer-upload.'.$extension,
        'stored_name' => basename($path),
        'disk' => 'local',
        'path' => $path,
        'mime_type' => $mimeType,
        'extension' => $extension,
        'size' => 12,
    ]);

    Storage::disk('local')->put($path, 'test document contents');

    return $document;
}

test('administrators can preview uploaded PDF documents inline', function () {
    Storage::fake('local');
    $admin = makeDocumentAdmin();
    $document = makeQuoteDocument('pdf', 'application/pdf', 'quote-requests/1/test.pdf');

    $this->actingAs($admin)
        ->get(route('admin.quote-request-documents.preview', $document))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Disposition', 'inline; filename=customer-upload.pdf');
});

test('customers cannot preview private uploaded documents', function () {
    Storage::fake('local');
    $document = makeQuoteDocument('pdf', 'application/pdf', 'quote-requests/1/private.pdf');
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.quote-request-documents.preview', $document))
        ->assertForbidden();
});

test('office documents remain downloadable but are not served inline', function () {
    Storage::fake('local');
    $admin = makeDocumentAdmin();
    $document = makeQuoteDocument(
        'docx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'quote-requests/1/contract.docx',
    );

    $this->actingAs($admin)
        ->get(route('admin.quote-request-documents.preview', $document))
        ->assertStatus(415);

    $this->actingAs($admin)
        ->get(route('admin.quote-request-documents.download', $document))
        ->assertOk();
});
