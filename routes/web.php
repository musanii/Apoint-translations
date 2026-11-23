<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\QuoteDraftController;
use App\Http\Controllers\Admin\QuoteRequestController as AdminQuoteRequestController;
use App\Http\Controllers\Admin\QuoteRequestDocumentController as AdminQuoteRequestDocumentController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\TestimonialController as AdminTestimonialController;
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
use App\Models\Testimonial;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome', [
    'services' => Service::where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug', 'description']),
    'languages' => Language::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'code', 'native_name', 'region']),\n    'testimonials' => Testimonial::query()->where('is_published', true)->orderByDesc('is_featured')->orderBy('sort_order')->orderByDesc('created_at')->get()->map(fn (Testimonial $testimonial) => [
        'id' => $testimonial->id,
        'customer_name' => $testimonial->customer_name,
        'company' => $testimonial->company,
        'service' => $testimonial->service,
        'quote' => $testimonial->quote,
        'rating' => $testimonial->rating,
        'avatar_url' => $testimonial->avatar_path ? asset('storage/'.$testimonial->avatar_path) : null,
        'is_featured' => $testimonial->is_featured,
    ]),
]))->name('home');
Route::get('/calculator/options', [CalculatorController::class, 'options'])->name('calculator.options');
Route::get('/calculator/calculate', [CalculatorController::class, 'calculate'])->name('calculator.calculate');

Route::middleware('auth')->group(function () {
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
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
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/{order}/start-processing', [AdminOrderController::class, 'startProcessing'])->name('orders.start-processing');
    Route::post('/orders/{order}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');
    Route::post('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/testimonials', [AdminTestimonialController::class, 'index'])->name('testimonials.index');\n    Route::get('/languages', [AdminLanguageController::class, 'index'])->name('languages.index');\n    Route::post('/languages', [AdminLanguageController::class, 'store'])->name('languages.store');\n    Route::put('/languages/{language}', [AdminLanguageController::class, 'update'])->name('languages.update');\n    Route::get('/currencies', [AdminCurrencyController::class, 'index'])->name('currencies.index');\n    Route::post('/currencies', [AdminCurrencyController::class, 'store'])->name('currencies.store');\n    Route::put('/currencies/{currency}', [AdminCurrencyController::class, 'update'])->name('currencies.update');\n    Route::get('/pricing-rules', [AdminPricingRuleController::class, 'index'])->name('pricing-rules.index');\n    Route::post('/pricing-rules', [AdminPricingRuleController::class, 'store'])->name('pricing-rules.store');\n    Route::put('/pricing-rules/{pricingRule}', [AdminPricingRuleController::class, 'update'])->name('pricing-rules.update');\n    Route::delete('/pricing-rules/{pricingRule}', [AdminPricingRuleController::class, 'destroy'])->name('pricing-rules.destroy');
    Route::post('/testimonials', [AdminTestimonialController::class, 'store'])->name('testimonials.store');
    Route::put('/testimonials/{testimonial}', [AdminTestimonialController::class, 'update'])->name('testimonials.update');
    Route::delete('/testimonials/{testimonial}', [AdminTestimonialController::class, 'destroy'])->name('testimonials.destroy');
    Route::get('/quote-requests/{quoteRequest}', [AdminQuoteRequestController::class, 'show'])->name('quote-requests.show');
    Route::post('/quote-requests/{quoteRequest}/review', [AdminQuoteRequestController::class, 'markUnderReview'])->name('quote-requests.review');
    Route::post('/quote-requests/{quoteRequest}/quotes', [QuoteDraftController::class, 'store'])->name('quote-requests.quotes.store');
    Route::post('/quotes/{quote}/send', [QuoteDraftController::class, 'send'])->name('quotes.send');
    Route::get('/quote-request-documents/{document}/preview', [AdminQuoteRequestDocumentController::class, 'preview'])->name('quote-request-documents.preview');
    Route::get('/quote-request-documents/{document}/download', [AdminQuoteRequestDocumentController::class, 'download'])->name('quote-request-documents.download');
});
Route::post('/mpesa/callback', [MpesaCallbackController::class, 'handle'])->name('mpesa.callback');
Route::post('/payhero/callback', [PayHeroCallbackController::class, 'handle'])->name('payhero.callback');
require __DIR__ . '/settings.php';
