<?php

/**
 * Application routes. $router is provided by public/index.php.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Admin;
use App\Controllers\Auth;
use App\Controllers\Client;
use App\Controllers\PublicSite;

// --- Public -----------------------------------------------------------------
$router->get('/', [PublicSite\HomeController::class, 'index'])->name('home');
$router->post('/contact', [PublicSite\ContactController::class, 'submit'])->name('contact.submit')->middleware('csrf');
$router->get('/health', [PublicSite\HealthController::class, 'index'])->name('health');
$router->get('/t', [PublicSite\VisitController::class, 'track'])->name('track');

// Inbound-email webhook (secret-gated, CSRF-exempt for external callers).
$router->post('/webhooks/email', [PublicSite\EmailWebhookController::class, 'inbound'])->name('webhooks.email');
$router->get('/robots.txt', [PublicSite\SeoController::class, 'robots'])->name('robots');
$router->get('/sitemap.xml', [PublicSite\SeoController::class, 'sitemap'])->name('sitemap');

// --- Live chat (public JSON endpoints) --------------------------------------
$router->post('/chat/start', [PublicSite\ChatController::class, 'start'])->name('chat.start')->middleware('csrf');
$router->post('/chat/message', [PublicSite\ChatController::class, 'message'])->name('chat.message')->middleware('csrf');
$router->get('/chat/poll', [PublicSite\ChatController::class, 'poll'])->name('chat.poll');

// --- PWA (manifest + service worker + offline shell) ------------------------
$router->get('/manifest.webmanifest', [PublicSite\PwaController::class, 'manifest'])->name('pwa.manifest');
$router->get('/sw.js', [PublicSite\PwaController::class, 'serviceWorker'])->name('pwa.sw');
$router->get('/offline', [PublicSite\PwaController::class, 'offline'])->name('pwa.offline');
$router->get('/pay/{token}', [PublicSite\PayController::class, 'show'])->name('pay.show');
$router->get('/pay/{token}/pdf', [PublicSite\PayController::class, 'pdf'])->name('pay.pdf');
$router->get('/pay/{token}/skrill', [PublicSite\PayController::class, 'skrill'])->name('pay.skrill');

// --- Blog (public, indexable) ----------------------------------------------
$router->get('/blog', [PublicSite\BlogController::class, 'index'])->name('blog.index');
$router->get('/blog/rss.xml', [PublicSite\SeoController::class, 'rss'])->name('blog.rss');
$router->get('/blog/{slug}', [PublicSite\BlogController::class, 'show'])->name('blog.show');

// --- Referral capture -------------------------------------------------------
$router->get('/r/{code}', [PublicSite\ReferralController::class, 'capture'])->name('referral.capture');

// --- Legal ------------------------------------------------------------------
$router->get('/terms', [PublicSite\LegalController::class, 'terms'])->name('legal.terms');
$router->get('/privacy', [PublicSite\LegalController::class, 'privacy'])->name('legal.privacy');
$router->get('/refund', [PublicSite\LegalController::class, 'refund'])->name('legal.refund');

// --- Guest auth -------------------------------------------------------------
$router->group(['middleware' => ['guest', 'csrf']], function ($router) {
    $router->get('/login', [Auth\LoginController::class, 'show'])->name('login');
    $router->post('/login', [Auth\LoginController::class, 'login']);

    $router->get('/register', [Auth\RegisterController::class, 'show'])->name('register');
    $router->post('/register', [Auth\RegisterController::class, 'register']);

    $router->get('/forgot-password', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    $router->post('/forgot-password', [Auth\PasswordResetController::class, 'sendLink'])->name('password.email');
    $router->get('/reset-password/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    $router->post('/reset-password', [Auth\PasswordResetController::class, 'update'])->name('password.update');
});

// Email confirmation — the token is the credential, so the GET link works for
// guests too (they may click it from a different device or browser).
$router->get('/email/verify/{token}', [Auth\EmailVerificationController::class, 'verify'])->name('email.verify');
$router->post('/email/verify/resend', [Auth\EmailVerificationController::class, 'resend'])->name('email.verify.resend')->middleware(['auth', 'csrf']);

$router->post('/logout', [Auth\LogoutController::class, 'logout'])->name('logout')->middleware(['auth', 'csrf']);
$router->post('/impersonate/leave', [\App\Controllers\ImpersonationController::class, 'leave'])->name('impersonate.leave')->middleware(['auth', 'csrf']);

// --- Two-factor login challenge (password verified, not yet fully signed in) ---
$router->get('/2fa', [Auth\TwoFactorController::class, 'challenge'])->name('2fa.challenge');
$router->post('/2fa', [Auth\TwoFactorController::class, 'verify'])->name('2fa.verify')->middleware('csrf');
$router->post('/2fa/resend', [Auth\TwoFactorController::class, 'resend'])->name('2fa.resend')->middleware('csrf');

// --- Account security (2FA setup) — any signed-in user ---
$router->group(['prefix' => 'account', 'middleware' => ['auth', 'csrf']], function ($router) {
    $router->get('/security', [\App\Controllers\SecurityController::class, 'show'])->name('security.show');
    $router->post('/security/setup', [\App\Controllers\SecurityController::class, 'setup'])->name('security.setup');
    $router->post('/security/confirm', [\App\Controllers\SecurityController::class, 'confirm'])->name('security.confirm');
    $router->post('/security/disable', [\App\Controllers\SecurityController::class, 'disable'])->name('security.disable');
});

// --- Admin (staff) ----------------------------------------------------------
$router->group(['prefix' => 'admin', 'middleware' => ['auth', 'role:admin,staff', 'csrf']], function ($router) {
    $router->get('/', [Admin\DashboardController::class, 'index'])->name('admin.dashboard');

    // Clients
    $router->get('/clients', [Admin\ClientController::class, 'index'])->name('admin.clients.index');
    $router->get('/clients/create', [Admin\ClientController::class, 'create'])->name('admin.clients.create');
    $router->post('/clients', [Admin\ClientController::class, 'store'])->name('admin.clients.store');
    $router->get('/clients/{id}', [Admin\ClientController::class, 'show'])->name('admin.clients.show');
    $router->get('/clients/{id}/edit', [Admin\ClientController::class, 'edit'])->name('admin.clients.edit');
    $router->put('/clients/{id}', [Admin\ClientController::class, 'update'])->name('admin.clients.update');
    $router->delete('/clients/{id}', [Admin\ClientController::class, 'destroy'])->name('admin.clients.destroy');

    // Engagements (client_services) — managed from the client page
    $router->post('/clients/{id}/credit', [Admin\ClientController::class, 'addCredit'])->name('admin.clients.credit');
    $router->post('/clients/{id}/apps', [Admin\ClientController::class, 'storeApp'])->name('admin.clients.apps.store');
    $router->post('/apps/{id}/delete', [Admin\ClientController::class, 'destroyApp'])->name('admin.apps.destroy');
    $router->post('/clients/{id}/engagements', [Admin\EngagementController::class, 'store'])->name('admin.engagements.store');
    $router->put('/engagements/{id}', [Admin\EngagementController::class, 'update'])->name('admin.engagements.update');
    $router->delete('/engagements/{id}', [Admin\EngagementController::class, 'destroy'])->name('admin.engagements.destroy');

    // Service catalogue
    $router->get('/services', [Admin\ServiceController::class, 'index'])->name('admin.services.index');
    $router->get('/services/create', [Admin\ServiceController::class, 'create'])->name('admin.services.create');
    $router->post('/services', [Admin\ServiceController::class, 'store'])->name('admin.services.store');
    $router->get('/services/{id}/edit', [Admin\ServiceController::class, 'edit'])->name('admin.services.edit');
    $router->put('/services/{id}', [Admin\ServiceController::class, 'update'])->name('admin.services.update');
    $router->delete('/services/{id}', [Admin\ServiceController::class, 'destroy'])->name('admin.services.destroy');

    // Payment-plan (instalment) requests
    $router->get('/installments', [Admin\InstallmentRequestController::class, 'index'])->name('admin.installments.index');
    $router->post('/installments/{id}/approve', [Admin\InstallmentRequestController::class, 'approve'])->name('admin.installments.approve');
    $router->post('/installments/{id}/decline', [Admin\InstallmentRequestController::class, 'decline'])->name('admin.installments.decline');

    // Blog
    $router->get('/blogs', [Admin\BlogController::class, 'index'])->name('admin.blogs.index');
    $router->get('/blogs/create', [Admin\BlogController::class, 'create'])->name('admin.blogs.create');
    $router->post('/blogs', [Admin\BlogController::class, 'store'])->name('admin.blogs.store');
    $router->get('/blogs/{id}/edit', [Admin\BlogController::class, 'edit'])->name('admin.blogs.edit');
    $router->put('/blogs/{id}', [Admin\BlogController::class, 'update'])->name('admin.blogs.update');
    $router->delete('/blogs/{id}', [Admin\BlogController::class, 'destroy'])->name('admin.blogs.destroy');

    // Project boards (Trello-style, per service line)
    $router->get('/boards', [Admin\BoardController::class, 'index'])->name('admin.boards.index');
    $router->get('/boards/{key}', [Admin\BoardController::class, 'show'])->name('admin.boards.show');
    $router->post('/boards/{key}/cards', [Admin\BoardController::class, 'storeCard'])->name('admin.boards.cards.store');
    $router->post('/boards/{key}/columns', [Admin\BoardController::class, 'storeColumn'])->name('admin.boards.columns.store');
    $router->put('/cards/{id}', [Admin\BoardController::class, 'updateCard'])->name('admin.cards.update');
    $router->delete('/cards/{id}', [Admin\BoardController::class, 'destroyCard'])->name('admin.cards.destroy');
    $router->post('/cards/{id}/move', [Admin\BoardController::class, 'moveCard'])->name('admin.cards.move');

    // Helpdesk
    $router->get('/tickets', [Admin\TicketController::class, 'index'])->name('admin.tickets.index');
    $router->get('/tickets/{id}', [Admin\TicketController::class, 'show'])->name('admin.tickets.show');
    $router->post('/tickets/{id}/reply', [Admin\TicketController::class, 'reply'])->name('admin.tickets.reply');
    $router->post('/tickets/{id}/status', [Admin\TicketController::class, 'status'])->name('admin.tickets.status');

    // Meetings
    $router->get('/meetings', [Admin\MeetingController::class, 'index'])->name('admin.meetings.index');
    $router->post('/meetings', [Admin\MeetingController::class, 'store'])->name('admin.meetings.store');
    $router->post('/meetings/{id}/confirm', [Admin\MeetingController::class, 'confirm'])->name('admin.meetings.confirm');
    $router->post('/meetings/{id}/cancel', [Admin\MeetingController::class, 'cancel'])->name('admin.meetings.cancel');

    // Visitor analytics (first-party)
    $router->get('/visitors', [Admin\VisitorController::class, 'index'])->name('admin.visitors.index');

    // Audit log (admin-only — see the middleware guard on sensitive routes below)
    $router->get('/audit-log', [Admin\AuditLogController::class, 'index'])->name('admin.audit.index');

    // Live chat (staff)
    $router->get('/chat', [Admin\ChatController::class, 'index'])->name('admin.chat.index');
    $router->get('/chat/{id}', [Admin\ChatController::class, 'show'])->name('admin.chat.show');
    $router->post('/chat/{id}/reply', [Admin\ChatController::class, 'reply'])->name('admin.chat.reply');
    $router->post('/chat/{id}/takeover', [Admin\ChatController::class, 'takeover'])->name('admin.chat.takeover');
    $router->post('/chat/{id}/close', [Admin\ChatController::class, 'close'])->name('admin.chat.close');

    // Mass email — admin only (enforced in controller)
    $router->get('/broadcast', [Admin\BroadcastController::class, 'index'])->name('admin.broadcast.index');
    $router->post('/broadcast/send', [Admin\BroadcastController::class, 'send'])->name('admin.broadcast.send');

    // Commissions (referral payouts) — admin only (enforced in controller)
    $router->get('/commissions', [Admin\CommissionController::class, 'index'])->name('admin.commissions.index');
    $router->post('/commissions/{id}/approve', [Admin\CommissionController::class, 'approve'])->name('admin.commissions.approve');
    $router->post('/commissions/{id}/pay', [Admin\CommissionController::class, 'markPaid'])->name('admin.commissions.pay');

    // Hosting (WHM reseller sync)
    $router->get('/hosting', [Admin\HostingController::class, 'index'])->name('admin.hosting.index');
    $router->post('/hosting/sync', [Admin\HostingController::class, 'sync'])->name('admin.hosting.sync');
    $router->post('/hosting/{id}/assign', [Admin\HostingController::class, 'assign'])->name('admin.hosting.assign');
    $router->post('/hosting/{id}/suspend', [Admin\HostingController::class, 'suspend'])->name('admin.hosting.suspend');
    $router->post('/hosting/{id}/unsuspend', [Admin\HostingController::class, 'unsuspend'])->name('admin.hosting.unsuspend');

    // Service categories (service lines)
    $router->post('/categories', [Admin\ServiceCategoryController::class, 'store'])->name('admin.categories.store');
    $router->put('/categories/{id}', [Admin\ServiceCategoryController::class, 'update'])->name('admin.categories.update');
    $router->delete('/categories/{id}', [Admin\ServiceCategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Invoices
    $router->get('/invoices', [Admin\InvoiceController::class, 'index'])->name('admin.invoices.index');
    $router->get('/invoices/create', [Admin\InvoiceController::class, 'create'])->name('admin.invoices.create');
    $router->post('/invoices', [Admin\InvoiceController::class, 'store'])->name('admin.invoices.store');
    $router->get('/invoices/{id}', [Admin\InvoiceController::class, 'show'])->name('admin.invoices.show');
    $router->get('/invoices/{id}/edit', [Admin\InvoiceController::class, 'edit'])->name('admin.invoices.edit');
    $router->put('/invoices/{id}', [Admin\InvoiceController::class, 'update'])->name('admin.invoices.update');
    $router->get('/invoices/{id}/pdf', [Admin\InvoiceController::class, 'pdf'])->name('admin.invoices.pdf');
    $router->post('/invoices/{id}/send', [Admin\InvoiceController::class, 'send'])->name('admin.invoices.send');
    $router->post('/invoices/{id}/payments', [Admin\InvoiceController::class, 'recordPayment'])->name('admin.invoices.payments.store');
    $router->post('/invoices/{id}/void', [Admin\InvoiceController::class, 'void'])->name('admin.invoices.void');
    $router->post('/invoices/{id}/late-fee/request-waiver', [Admin\InvoiceController::class, 'requestLateFeeWaiver'])->name('admin.invoices.latefee.request');
    $router->post('/invoices/{id}/late-fee/waive', [Admin\InvoiceController::class, 'waiveLateFee'])->name('admin.invoices.latefee.waive');
    $router->delete('/invoices/{id}', [Admin\InvoiceController::class, 'destroy'])->name('admin.invoices.destroy');

    // Users (admin only — enforced in the controller)
    $router->get('/users', [Admin\UserController::class, 'index'])->name('admin.users.index');
    $router->get('/users/create', [Admin\UserController::class, 'create'])->name('admin.users.create');
    $router->post('/users', [Admin\UserController::class, 'store'])->name('admin.users.store');
    $router->get('/users/{id}/edit', [Admin\UserController::class, 'edit'])->name('admin.users.edit');
    $router->put('/users/{id}', [Admin\UserController::class, 'update'])->name('admin.users.update');
    $router->delete('/users/{id}', [Admin\UserController::class, 'destroy'])->name('admin.users.destroy');
    $router->post('/users/{id}/login-as', [\App\Controllers\ImpersonationController::class, 'start'])->name('admin.users.loginas');

    // Settings
    $router->get('/settings', [Admin\SettingController::class, 'edit'])->name('admin.settings.edit');
    $router->put('/settings', [Admin\SettingController::class, 'update'])->name('admin.settings.update');
});

// --- Client portal ----------------------------------------------------------
$router->group(['prefix' => 'portal', 'middleware' => ['auth', 'role:client', 'terms', 'csrf']], function ($router) {
    $router->get('/', [Client\DashboardController::class, 'index'])->name('portal.dashboard');
    $router->get('/accept-terms', [Client\TermsController::class, 'show'])->name('portal.terms.show');
    $router->post('/accept-terms', [Client\TermsController::class, 'accept'])->name('portal.terms.accept');
    $router->get('/order', [Client\OrderController::class, 'index'])->name('portal.order.index');
    $router->get('/order/{service}', [Client\OrderController::class, 'show'])->name('portal.order.show');
    $router->post('/order/{service}', [Client\OrderController::class, 'place'])->name('portal.order.place');
    $router->get('/intake/{engagement}', [Client\IntakeController::class, 'show'])->name('portal.intake.show');
    $router->post('/intake/{engagement}', [Client\IntakeController::class, 'store'])->name('portal.intake.store');
    $router->get('/services', [Client\ServiceController::class, 'index'])->name('portal.services');
    $router->post('/services/{id}/cancel', [Client\ServiceController::class, 'cancel'])->name('portal.services.cancel');
    $router->get('/project', [Client\ProjectController::class, 'index'])->name('portal.project');
    $router->get('/hosting', [Client\HostingController::class, 'index'])->name('portal.hosting');
    $router->get('/meetings', [Client\MeetingController::class, 'index'])->name('portal.meetings');
    $router->post('/meetings', [Client\MeetingController::class, 'store'])->name('portal.meetings.request');
    $router->post('/hosting/{id}/login', [Client\HostingController::class, 'login'])->name('portal.hosting.login');
    $router->get('/refer', [Client\ReferController::class, 'index'])->name('portal.refer');
    $router->get('/support', [Client\SupportController::class, 'index'])->name('portal.support.index');
    $router->get('/support/new', [Client\SupportController::class, 'create'])->name('portal.support.create');
    $router->post('/support', [Client\SupportController::class, 'store'])->name('portal.support.store');
    $router->get('/support/{id}', [Client\SupportController::class, 'show'])->name('portal.support.show');
    $router->post('/support/{id}/reply', [Client\SupportController::class, 'reply'])->name('portal.support.reply');
    $router->get('/invoices', [Client\InvoiceController::class, 'index'])->name('portal.invoices.index');
    $router->get('/invoices/{id}', [Client\InvoiceController::class, 'show'])->name('portal.invoices.show');
    $router->post('/invoices/{id}/apply-credit', [Client\InvoiceController::class, 'applyCredit'])->name('portal.invoices.credit');
    $router->get('/invoices/{id}/pdf', [Client\InvoiceController::class, 'pdf'])->name('portal.invoices.pdf');
    $router->get('/profile', [Client\ProfileController::class, 'edit'])->name('portal.profile.edit');
    $router->put('/profile', [Client\ProfileController::class, 'update'])->name('portal.profile.update');
});
