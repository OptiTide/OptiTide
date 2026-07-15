<?php

namespace App\Core;

use App\Core\Exceptions\HttpException;
use App\Core\Exceptions\ValidationException;
use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(): void
    {
        error_reporting(E_ALL);

        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            if (! (error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (Throwable $e) {
            static::render($e, null)->send();
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                static::render(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']), null)->send();
            }
        });
    }

    public static function render(Throwable $e, ?Request $request): Response
    {
        static::log($e);

        $status = $e instanceof HttpException ? $e->status : 500;
        $debug = (bool) config('app.debug', false);

        $wantsJson = $request?->wantsJson() ?? (PHP_SAPI === 'cli');

        if ($e instanceof ValidationException) {
            $status = 422;
        }

        if ($wantsJson) {
            $payload = ['message' => $status >= 500 && ! $debug ? 'Server error.' : $e->getMessage()];
            if ($e instanceof ValidationException) {
                $payload['errors'] = $e->errors;
            }
            if ($debug && $status >= 500) {
                $payload['exception'] = $e::class;
                $payload['file'] = $e->getFile() . ':' . $e->getLine();
            }

            return Response::json($payload, $status);
        }

        if ($debug && $status >= 500) {
            return Response::make(static::debugPage($e), $status, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $template = View::exists("errors.$status") ? "errors.$status" : 'errors.generic';

        // Never surface a raw exception message on a 5xx in production — only
        // deliberate 4xx HttpException messages ("Invoice not found") are shown.
        $message = ($status >= 500 && ! $debug)
            ? 'Something went wrong on our end. Please try again.'
            : $e->getMessage();

        return Response::view($template, [
            'status'  => $status,
            'message' => $message,
        ], $status);
    }

    protected static function log(Throwable $e): void
    {
        $line = sprintf(
            "[%s] %s: %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        $path = base_path('storage/logs/app.log');
        if (is_dir(dirname($path))) {
            @file_put_contents($path, $line, FILE_APPEND);
        }

        error_log(trim($line));
    }

    protected static function debugPage(Throwable $e): string
    {
        $title = e($e::class);
        $message = e($e->getMessage());
        $location = e($e->getFile() . ':' . $e->getLine());
        $trace = e($e->getTraceAsString());

        return <<<HTML
        <!doctype html><html><head><meta charset="utf-8"><title>Error — {$title}</title>
        <style>
        body{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;background:#0f172a;color:#e2e8f0;margin:0;padding:2rem;line-height:1.5}
        .card{max-width:960px;margin:0 auto;background:#1e293b;border:1px solid #334155;border-radius:12px;padding:1.5rem 2rem}
        h1{color:#f87171;font-size:1.1rem}.msg{font-size:1.25rem;margin:.5rem 0 1rem}.loc{color:#94a3b8}
        pre{background:#0f172a;padding:1rem;border-radius:8px;overflow:auto;font-size:.8rem;color:#cbd5e1}
        </style></head><body><div class="card">
        <h1>{$title}</h1><div class="msg">{$message}</div>
        <div class="loc">{$location}</div><pre>{$trace}</pre>
        </div></body></html>
        HTML;
    }
}
