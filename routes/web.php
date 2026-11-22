<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QuoteDraftController;
use App\Http\Controllers\Admin\QuoteRequestController as AdminQuoteRequestController;
use App\Http\Controllers\Admin\QuoteRequestDocumentController as AdminQuoteRequestDocumentController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerQuoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MpesaCallbackController;
use App\Http\Controllers\PayHeroCallbackController;
use App\Http\Controllers\QuoteRequestController;
use App\Http\Controllers\QuoteRequestDocumentController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome', [
    'services' => Service::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'description']),
]))->name('home');
Route::get('/calculator/options', [CalculatorController::class, 'options'])->name('calculator.options');
Route::get('/calculator/calculate', [CalculatorController::class, 'calculate'])->name('calculator.calculate');

Route::middleware('auth')->group(function () {
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/quote-requests/create', [QuoteRequestController::class, 'create'])->name('quote-requests.create');
    Route::get('/quotes/{quote}', [CustomerQuoteController::class, 'show'])->name('quotes.show');
    Route::post('/quotes/{quote}/accept', [CustomerQuoteController::class, 'accept'])->name('quotes.accept');
    Route::post('/quotes/{quote}/reject', [CustomerQuoteController::class, 'reject'])->name('quotes.reject');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('orders.payments.store');
    Route::post('/quote-requests', [QuoteRequestController::class, 'store'])->name('quote-requests.store');
    Route::get('/quote-requests/{quoteRequest}', [QuoteRequestController::class, 'show'])->name('quote-requests.show');
    Route::post('/quote-requests/{quoteRequest}/documents', [QuoteRequestDocumentController::class, 'store'])->name('quote-requests.documents.store');
});
Route::middleware('auth')->get('/dashboard', DashboardController::class)->name('dashboard');

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');
    Route::get('/quote-requests', [AdminQuoteRequestController::class, 'index'])->name('quote-requests.index');
    Route::get('/quote-requests/{quoteRequest}', [AdminQuoteRequestController::class, 'show'])->name('quote-requests.show');
    Route::post('/quote-requests/{quoteRequest}/review', [AdminQuoteRequestController::class, 'markUnderReview'])->name('quote-requests.review');
    Route::post('/quote-requests/{quoteRequest}/quotes', [QuoteDraftController::class, 'store'])->name('quote-requests.quotes.store');
    Route::post('/quotes/{quote}/send', [QuoteDraftController::class, 'send'])->name('quotes.send');
    Route::get('/quote-request-documents/{document}/download', [AdminQuoteRequestDocumentController::class, 'download'])->name('quote-request-documents.download');
});
Route::post('/mpesa/callback', [MpesaCallbackController::class, 'handle'])->name('mpesa.callback');
Route::post('/payhero/callback', [PayHeroCallbackController::class, 'handle'])->name('payhero.callback');
require __DIR__ . '/settings.php';
