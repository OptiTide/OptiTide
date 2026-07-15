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

Session::start();

$request = Request::capture();
$GLOBALS['__request'] = $request;

// Remember the last visited page so back() and validation redirects land right.
if ($request->method() === 'GET' && ! $request->wantsJson()) {
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
    $response = Response::redirect(Session::get('_previous_url', '/'));
} catch (\Throwable $e) {
    $response = ErrorHandler::render($e, $request);
}

$response->send();
