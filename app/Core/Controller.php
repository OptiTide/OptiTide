<?php

namespace App\Core;

use App\Core\Exceptions\HttpException;

abstract class Controller
{
    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return Response::view($template, $data, $status);
    }

    protected function json(mixed $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return Response::redirect($url, $status);
    }

    protected function redirectRoute(string $name, array $params = []): Response
    {
        return Response::redirect(route($name, $params));
    }

    protected function back(): Response
    {
        return Response::redirect(safe_back_url());
    }

    /** Validate request input; throws ValidationException handled by the kernel. */
    protected function validate(Request $request, array $rules, array $labels = []): array
    {
        return Validator::make($request->all(), $rules, $labels)->validate();
    }

    protected function authorize(bool $allowed, string $message = 'This action is unauthorized.'): void
    {
        if (! $allowed) {
            throw new HttpException(403, $message);
        }
    }

    protected function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }

    /** Flash a status message for the next request. */
    protected function flash(string $type, string $message): void
    {
        Session::flash($type, $message);
    }
}
