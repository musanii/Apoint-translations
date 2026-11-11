<?php

use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\QuoteRequestDocumentController;

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});
Route::post('/quote-requests/{quoteRequest}/documents', [QuoteRequestDocumentController::class, 'store'])->middleware('auth')->name('quote-requests.documents.store');


Route::post('/mpesa/callback', [MpesaCallbackController::class, 'handle'])
    ->name('mpesa.callback');
require __DIR__ . '/settings.php';
