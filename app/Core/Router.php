<?php

namespace App\Core;

use App\Core\Exceptions\HttpException;
use Closure;

final class Router
{
    /** @var array<string,Route[]> */
    protected array $routes = [];

    /** @var array<int,array{prefix:string,middleware:array}> */
    protected array $groupStack = [];

    /** @var array<string,Route> */
    protected static array $named = [];

    /** @var array<string,class-string> */
    protected static array $aliases = [];

    public function get(string $uri, mixed $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, mixed $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, mixed $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function patch(string $uri, mixed $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    public function delete(string $uri, mixed $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = [
            'prefix'     => $attributes['prefix'] ?? '',
            'middleware' => (array) ($attributes['middleware'] ?? []),
        ];

        $callback($this);

        array_pop($this->groupStack);
    }

    protected function addRoute(string $method, string $uri, mixed $action): Route
    {
        $prefix = '';
        $middleware = [];

        foreach ($this->groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'], '/');
            $middleware = array_merge($middleware, $group['middleware']);
        }

        $uri = '/' . trim(trim($prefix, '/') . '/' . trim($uri, '/'), '/');
        $uri = $uri === '' ? '/' : $uri;

        $route = new Route($method, $uri, $action);
        $route->middleware = $middleware;

        $this->routes[$method][] = $route;

        return $route;
    }

    public static function alias(string $name, string $class): void
    {
        static::$aliases[$name] = $class;
    }

    public static function registerName(string $name, Route $route): void
    {
        static::$named[$name] = $route;
    }

    public static function url(string $name, array $params = []): string
    {
        if (! isset(static::$named[$name])) {
            throw new HttpException(500, "Route [$name] is not defined.");
        }

        $uri = static::$named[$name]->uri;

        foreach ($params as $key => $value) {
            $uri = preg_replace('#\{' . preg_quote($key, '#') . '\??\}#', rawurlencode((string) $value), $uri);
        }

        // Drop any unfilled optional segments.
        $uri = preg_replace('#/\{[^/]+\?\}#', '', $uri);
        $uri = preg_replace('#\{[^/]+\?\}#', '', $uri);

        return $uri;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($this->compile($route->uri), $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setRouteParams($params);

                return $this->runPipeline($route, $request, $params);
            }
        }

        // Distinguish 405 from 404 for a matched path on another verb.
        foreach ($this->routes as $verb => $routes) {
            foreach ($routes as $route) {
                if (preg_match($this->compile($route->uri), $path)) {
                    throw new HttpException(405, 'Method not allowed.');
                }
            }
        }

        throw new HttpException(404, 'Not found.');
    }

    protected function compile(string $uri): string
    {
        $pattern = preg_replace_callback('#\{(\w+)(\?)?\}#', function ($m) {
            return $m[2] ?? false
                ? '(?:/(?P<' . $m[1] . '>[^/]+))?'
                : '(?P<' . $m[1] . '>[^/]+)';
        }, $uri);

        // For optional params the leading slash is absorbed above.
        $pattern = str_replace('/(?:/', '(?:/', $pattern);

        return '#^' . $pattern . '$#';
    }

    protected function runPipeline(Route $route, Request $request, array $params): Response
    {
        $core = fn (Request $req): Response => $this->runAction($route->action, $req, $params);

        $pipeline = array_reduce(
            array_reverse($route->middleware),
            function (Closure $next, string $name): Closure {
                return function (Request $req) use ($next, $name): Response {
                    return $this->resolveMiddleware($name)->handle($req, $next);
                };
            },
            $core
        );

        return $pipeline($request);
    }

    protected function resolveMiddleware(string $name): Middleware
    {
        // Support "alias:arg" (e.g. role:admin).
        $argument = null;
        if (str_contains($name, ':')) {
            [$name, $argument] = explode(':', $name, 2);
        }

        $class = static::$aliases[$name] ?? $name;

        if (! class_exists($class)) {
            throw new HttpException(500, "Middleware [$name] is not registered.");
        }

        return $argument === null ? new $class() : new $class($argument);
    }

    protected function runAction(mixed $action, Request $request, array $params): Response
    {
        if ($action instanceof Closure) {
            $result = $action($request, ...array_values($params));
        } else {
            [$class, $method] = $action;
            $controller = new $class();
            $result = $controller->$method($request, ...array_values($params));
        }

        return $result instanceof Response ? $result : Response::make((string) $result);
    }
}
