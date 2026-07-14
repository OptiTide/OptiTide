<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContractSignatureController;
use App\Http\Controllers\IntakeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\NotFoundController;
use App\Http\Controllers\ProofingController;
use App\Http\Controllers\SeoAuditController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\SubscriptionCheckoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/services', [StorefrontController::class, 'services'])->name('services.index');
Route::get('/services/{product:slug}', [StorefrontController::class, 'service'])->name('services.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
Route::delete('/cart/{product}', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

// Instant SEO audit lead magnet. Tight throttle — each POST triggers a scrape
// + Claude call + PDF + email.
Route::get('/seo-audit', [SeoAuditController::class, 'show'])->name('seo-audit.show');
Route::post('/seo-audit', [SeoAuditController::class, 'store'])
    ->middleware('throttle:3,10')
    ->name('seo-audit.store');

// Public blog (SEO engine output) + crawler files.
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{blog:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/sitemap.xml', [BlogController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [BlogController::class, 'robots'])->name('robots');

// Standalone launch/holding page (route:cache-safe — Route::view, no closure).
Route::view('/coming-soon', 'coming-soon')->name('coming-soon');

// Local SEO landing pages: /web-design-sydney etc. (before the CMS catch-all).
Route::get('/web-design-{city}', [LocationController::class, 'show'])
    ->where('city', '[a-z0-9-]+')
    ->name('location.show');

Route::middleware('auth')->group(function () {
    // Each POST opens a Stripe Checkout Session (an external call); throttle per
    // authenticated user to prevent session-spam. Filament login/register are
    // already rate-limited by Filament (5/min and 2/min).
    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('checkout.store');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/subscribe/{product:slug}', [SubscriptionCheckoutController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('subscribe.store');

    Route::get('/contracts/{contract}/sign', [ContractSignatureController::class, 'show'])->name('contracts.sign.show');
    Route::post('/contracts/{contract}/sign', [ContractSignatureController::class, 'store'])->name('contracts.sign');
    Route::get('/contracts/{contract}/download', [ContractSignatureController::class, 'download'])->name('contracts.download');

    Route::get('/orders/{order}/brief', [IntakeController::class, 'show'])->name('brief.show');
    Route::post('/orders/{order}/brief', [IntakeController::class, 'store'])->name('brief.store');
    Route::get('/orders/{order}/brief/asset', [IntakeController::class, 'asset'])->name('brief.asset');

    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');

    Route::get('/orders/{order}/proof', [ProofingController::class, 'show'])->name('proofing.show');
    Route::get('/orders/{order}/preview', [ProofingController::class, 'preview'])->name('proofing.preview');
    Route::post('/orders/{order}/proof/approve', [ProofingController::class, 'approve'])->name('proofing.approve');
    Route::post('/orders/{order}/proof/changes', [ProofingController::class, 'requestChanges'])->name('proofing.changes');
    Route::post('/orders/{order}/proof/annotate', [ProofingController::class, 'annotate'])->name('proofing.annotate');
});

// Replaces Cashier's auto-registered webhook route (Cashier::ignoreRoutes in
// AppServiceProvider) so one endpoint serves subscription + one-time events.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook');

// Neutralize the sign-pad package's built-in signing route (shared per-class
// token). All signing goes through our auth + ownership-checked controller;
// registering last makes this win the POST /creagia/sign-pad match. Uses an
// invokable controller (not a Closure) so `route:cache` works in production.
Route::any('creagia/sign-pad', NotFoundController::class);

// CMS pages at the root (e.g. /privacy). Registered LAST and constrained to a
// single lowercase slug segment so it never shadows a named/panel route; the
// model binding resolves only existing pages (missing => 404).
Route::get('/{page:slug}', [CmsPageController::class, 'show'])
    ->where('page', '[a-z0-9][a-z0-9\-]*')
    ->name('cms.show');
