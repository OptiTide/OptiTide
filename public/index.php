<?php

use App\Core\ErrorHandler;
use App\Core\Exceptions\ValidationException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

// Let the PHP built-in dev server serve existing static files directly.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/bin/bootstrap.php';

// One canonical URL per page (www -> apex, http -> https, /Path -> /path, no
// trailing slash, retired blog URLs -> their replacements). Before Session::start
// so a crawler never gets a session cookie on a URL that is about to 301, and
// before routing so a redirect costs nothing.
App\Core\CanonicalUrl::enforce();

Session::start();

$request = Request::capture();
$GLOBALS['__request'] = $request;

// Remember the last visited PAGE so back() and validation redirects land right.
//
// This used to record EVERY non-JSON GET, which is how logging in could dump you
// on the raw /sw.js source. A page does not only fetch itself: the browser also
// fetches /sw.js (the service-worker update check) and the page fires a /t
// analytics beacon, and both are routed GETs that reach this line. wantsJson()
// only looks for 'application/json', and those requests send Accept: */*, so they
// sailed through and overwrote _previous_url with their own URL. Then a WRONG
// PASSWORD — which is the only path that reads it, via back() — redirected to
// whichever subresource happened to land last. A successful login never reads it,
// which is why this survived three attempts to find it.
if (Request::isPageNavigation($request)) {
    Session::put('_previous_url', $request->uri());
}

$router = new Router();
require BASE_PATH . '/routes/web.php';

try {
    $response = $router->dispatch($request);
} catch (ValidationException $e) {
    Session::flash('errors', $e->errors);
    Session::flash('_old', array_diff_key(
        $request->all(),
        array_flip(['password', 'password_confirmation', 'current_password', '_token', '_method'])
    ));
    // Validated, not raw — a session poisoned before the write-side fix above
    // would otherwise still bounce a failed form submit onto /sw.js or /t.
    $response = Response::redirect(safe_back_url());
} catch (\Throwable $e) {
    $response = ErrorHandler::render($e, $request);
}

$response->send();
