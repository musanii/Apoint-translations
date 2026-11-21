<?php

use App\Models\Currency;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a quote request can have multiple documents', function () {
    $user = User::factory()->create();

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

    $quoteRequest = $user->quoteRequests()->create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
        'instructions' => 'Please translate these documents.',
    ]);

    $quoteRequest->documents()->createMany([
        [
            'original_name' => 'birth-certificate.pdf',
            'stored_name' => 'abc123.pdf',
            'disk' => 'local',
            'path' => 'quote-requests/1/abc123.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 204800,
        ],
        [
            'original_name' => 'academic-transcript.docx',
            'stored_name' => 'def456.docx',
            'disk' => 'local',
            'path' => 'quote-requests/1/def456.docx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'extension' => 'docx',
            'size' => 512000,
        ],
    ]);

    expect($quoteRequest->documents)->toHaveCount(2);

    expect($quoteRequest->documents->first()->original_name)
        ->toBe('birth-certificate.pdf');

    expect($quoteRequest->documents->last()->extension)
        ->toBe('docx');
});

test('a document belongs to its quote request', function () {
    $user = User::factory()->create();

    $service = Service::create([
        'name' => 'Proofreading',
        'slug' => 'proofreading',
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

    $quoteRequest = $user->quoteRequests()->create([
        'service_id' => $service->id,
        'currency_id' => $currency->id,
    ]);

    $document = $quoteRequest->documents()->create([
        'original_name' => 'report.pdf',
        'stored_name' => 'xyz789.pdf',
        'disk' => 'local',
        'path' => 'quote-requests/1/xyz789.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'size' => 102400,
    ]);

    expect($document->quoteRequest->id)->toBe($quoteRequest->id);
    expect($document->size)->toBe(102400);
});
