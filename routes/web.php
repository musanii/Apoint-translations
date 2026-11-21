<?php

use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\PayHeroCallbackController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\QuoteRequestDocumentController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'services' => Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'description']),
    ]);
})->name('home');

Route::get('/calculator/options', [CalculatorController::class, 'options'])
    ->name('calculator.options');

Route::get('/calculator/calculate', [CalculatorController::class, 'calculate'])
    ->name('calculator.calculate');

Route::middleware(['auth'])->group(function () {
    Route::get('/quote-requests/create', [QuoteRequestController::class, 'create'])
        ->name('quote-requests.create');

    Route::post('/quote-requests', [QuoteRequestController::class, 'store'])
        ->name('quote-requests.store');

    Route::get('/quote-requests/{quoteRequest}', [QuoteRequestController::class, 'show'])
        ->name('quote-requests.show');

    Route::post('/quote-requests/{quoteRequest}/documents', [QuoteRequestDocumentController::class, 'store'])
        ->name('quote-requests.documents.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::post('/mpesa/callback', [MpesaCallbackController::class, 'handle'])
    ->name('mpesa.callback');

Route::post('/payhero/callback', [PayHeroCallbackController::class, 'handle'])
    ->name('payhero.callback');

require __DIR__.'/settings.php';
