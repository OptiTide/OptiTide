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
$router->get('/robots.txt', [PublicSite\SeoController::class, 'robots'])->name('robots');
$router->get('/sitemap.xml', [PublicSite\SeoController::class, 'sitemap'])->name('sitemap');
$router->get('/pay/{token}', [PublicSite\PayController::class, 'show'])->name('pay.show');
$router->get('/pay/{token}/pdf', [PublicSite\PayController::class, 'pdf'])->name('pay.pdf');

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

$router->post('/logout', [Auth\LogoutController::class, 'logout'])->name('logout')->middleware(['auth', 'csrf']);

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
    $router->delete('/invoices/{id}', [Admin\InvoiceController::class, 'destroy'])->name('admin.invoices.destroy');

    // Users (admin only — enforced in the controller)
    $router->get('/users', [Admin\UserController::class, 'index'])->name('admin.users.index');
    $router->get('/users/create', [Admin\UserController::class, 'create'])->name('admin.users.create');
    $router->post('/users', [Admin\UserController::class, 'store'])->name('admin.users.store');
    $router->get('/users/{id}/edit', [Admin\UserController::class, 'edit'])->name('admin.users.edit');
    $router->put('/users/{id}', [Admin\UserController::class, 'update'])->name('admin.users.update');
    $router->delete('/users/{id}', [Admin\UserController::class, 'destroy'])->name('admin.users.destroy');

    // Settings
    $router->get('/settings', [Admin\SettingController::class, 'edit'])->name('admin.settings.edit');
    $router->put('/settings', [Admin\SettingController::class, 'update'])->name('admin.settings.update');
});

// --- Client portal ----------------------------------------------------------
$router->group(['prefix' => 'portal', 'middleware' => ['auth', 'role:client', 'csrf']], function ($router) {
    $router->get('/', [Client\DashboardController::class, 'index'])->name('portal.dashboard');
    $router->get('/services', [Client\ServiceController::class, 'index'])->name('portal.services');
    $router->get('/invoices', [Client\InvoiceController::class, 'index'])->name('portal.invoices.index');
    $router->get('/invoices/{id}', [Client\InvoiceController::class, 'show'])->name('portal.invoices.show');
    $router->get('/invoices/{id}/pdf', [Client\InvoiceController::class, 'pdf'])->name('portal.invoices.pdf');
    $router->get('/profile', [Client\ProfileController::class, 'edit'])->name('portal.profile.edit');
    $router->put('/profile', [Client\ProfileController::class, 'update'])->name('portal.profile.update');
});
