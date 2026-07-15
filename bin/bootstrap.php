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

date_default_timezone_set(config('app.timezone', 'UTC'));

$debug = (bool) config('app.debug', false);
ini_set('display_errors', $debug ? '1' : '0');

ErrorHandler::register();

View::setBasePath(BASE_PATH . '/resources/views');

Router::alias('auth', App\Middleware\Authenticate::class);
Router::alias('guest', App\Middleware\RedirectIfAuthenticated::class);
Router::alias('role', App\Middleware\EnsureRole::class);
Router::alias('csrf', App\Middleware\VerifyCsrf::class);
