<?php

use App\Models\Currency;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->user = User::factory()->create();

    $service = Service::create([
        'name' => 'Document Translation',
        'slug' => 'document-translation',
        'is_active' => true,
    ]);

    $currency = Currency::create([
        'code' => 'KES',
        'name' => 'Kenyan Shilling',
        'symbol' => 'KSh',
        'decimal_places' => 2,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->quoteRequest = $this->user->quoteRequests()->create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
    ]);
});

test('a customer can upload a document to their own quote request', function () {
    $file = UploadedFile::fake()->createWithContent(
        'contract.pdf',
        "%PDF-1.4\nA sample PDF document."
    );

    $response = $this->actingAs($this->user)->post(
        route('quote-requests.documents.store', $this->quoteRequest),
        ['document' => $file]
    );

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('quote_request_documents', [
        'quote_request_id' => $this->quoteRequest->id,
        'original_name' => 'contract.pdf',
        'extension' => 'pdf',
    ]);

    $document = $this->quoteRequest->documents()->first();

    Storage::disk('local')->assertExists($document->path);
});

test('a customer cannot upload a document to another customers quote request', function () {
    $anotherUser = User::factory()->create();

    $anotherQuoteRequest = $anotherUser->quoteRequests()->create([
        'service_id' => $this->quoteRequest->service_id,
        'currency_id' => $this->quoteRequest->currency_id,
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'contract.pdf',
        "%PDF-1.4\nA sample PDF document."
    );

    $response = $this->actingAs($this->user)->post(
        route('quote-requests.documents.store', $anotherQuoteRequest),
        ['document' => $file]
    );

    $response->assertForbidden();

    expect($anotherQuoteRequest->documents()->count())->toBe(0);
});

test('a guest cannot upload a document', function () {
    $response = $this->post(
        route('quote-requests.documents.store', $this->quoteRequest),
        []
    );

    $response->assertRedirect(route('login'));

    expect($this->quoteRequest->documents()->count())->toBe(0);
});
