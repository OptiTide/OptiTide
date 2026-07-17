<?php

// Dev-only shim for `php -S`: the built-in server must serve existing static
// files verbatim and hand everything else to the front controller.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/public' . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';
