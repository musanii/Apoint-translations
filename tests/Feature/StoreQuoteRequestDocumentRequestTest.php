<?php

use App\Http\Requests\StoreQuoteRequestDocumentRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

test('it requires an uploaded document', function () {
    $request = new StoreQuoteRequestDocumentRequest();

    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('document'))->toBeTrue();
});

test('it rejects unsupported file types', function () {
    $file = UploadedFile::fake()->create('script.exe', 100);

    $request = new StoreQuoteRequestDocumentRequest();

    $validator = Validator::make(
        ['document' => $file],
        $request->rules()
    );

    expect($validator->fails())->toBeTrue();
});

test('it rejects documents larger than 10 MB', function () {
    $file = UploadedFile::fake()->create('large.pdf', 10241);

    $request = new StoreQuoteRequestDocumentRequest();

    $validator = Validator::make(
        ['document' => $file],
        $request->rules()
    );

    expect($validator->fails())->toBeTrue();
});