<?php

use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\PayHeroCallbackController;
use App\Http\Controllers\QuoteRequestDocumentController;
use App\Models\Service;
use Inertia\Inertia;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'services' => Service::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'description']),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});
Route::post('/quote-requests/{quoteRequest}/documents', [QuoteRequestDocumentController::class, 'store'])->middleware('auth')->name('quote-requests.documents.store');


Route::post('/mpesa/callback', [MpesaCallbackController::class, 'handle'])
    ->name('mpesa.callback');
Route::post('/payhero/callback', [PayHeroCallbackController::class, 'handle'])
    ->name('payhero.callback');
require __DIR__ . '/settings.php';
