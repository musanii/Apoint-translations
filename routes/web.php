<?php

use App\Http\Controllers\QuoteRequestDocumentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    
});
Route::post('/quote-requests/{quoteRequest}/documents',[QuoteRequestDocumentController::class,'store'])->middleware('auth')->name('quote-requests.documents.store');

require __DIR__.'/settings.php';
