<?php

/**
 * Shared bootstrap for both the web front controller and the CLI console.
 * Loads env + config, wires error handling, the view path, and middleware
 * aliases. Does NOT start a session (the web entrypoint does that).
 */

use App\Core\Config;
use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\Router;
use App\Core\View;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config');

// Admin-editable settings (stored in the DB with dot-path keys) override the
// .env config, so company/payment details can be managed online. Guarded so it
// no-ops before the table exists (e.g. during migrate) or if the DB is down.
try {
    if (App\Core\Schema::hasTable('settings')) {
        foreach (App\Models\Setting::all() as $setting) {
            if (str_contains($setting['key'], '.') && (string) $setting['value'] !== '') {
                Config::set($setting['key'], $setting['value']);
            }
        }
    }
} catch (\Throwable $e) {
    // Database not ready — fall back to .env values.
}

date_default_timezone_set(config('app.timezone', 'UTC'));

$debug = (bool) config('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');

ErrorHandler::register();

View::setBasePath(BASE_PATH . '/resources/views');

Router::alias('auth', App\Middleware\Authenticate::class);
Router::alias('guest', App\Middleware\RedirectIfAuthenticated::class);
Router::alias('role', App\Middleware\EnsureRole::class);
Router::alias('csrf', App\Middleware\VerifyCsrf::class);
Router::alias('terms', App\Middleware\EnsureTermsAccepted::class);
