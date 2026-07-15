<?php

namespace App\Core;

final class Route
{
    public array $middleware = [];
    public ?string $name = null;

    /** @param array|callable $action */
    public function __construct(public string $method, public string $uri, public mixed $action)
    {
    }

    public function name(string $name): static
    {
        $this->name = $name;
        Router::registerName($name, $this);

        return $this;
    }

    public function middleware(string|array $middleware): static
    {
        $this->middleware = array_merge($this->middleware, (array) $middleware);

        return $this;
    }
}
