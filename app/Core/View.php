<?php

namespace App\Core;

use RuntimeException;

/**
 * Tiny PHP template engine with single-level layout inheritance.
 *
 * A template opts into a layout with $this->extends('layouts.admin') and
 * defines blocks with $this->section('content') ... $this->endSection().
 * The layout emits them with $this->yield('content'). Partials render inline
 * via $this->insert('partials.flash').
 */
final class View
{
    protected static string $basePath = '';

    protected ?string $layout = null;

    /** @var array<string,string> */
    protected array $sections = [];

    /** @var string[] */
    protected array $sectionStack = [];

    protected array $data = [];

    public static function setBasePath(string $path): void
    {
        static::$basePath = rtrim($path, '/');
    }

    public static function exists(string $template): bool
    {
        return is_file(static::path($template));
    }

    public static function render(string $template, array $data = []): string
    {
        return (new self())->renderView($template, $data);
    }

    protected function renderView(string $template, array $data): string
    {
        $this->data = $data;
        $childOutput = $this->renderFile($template, $data);

        if ($this->layout !== null) {
            if (! isset($this->sections['content'])) {
                $this->sections['content'] = $childOutput;
            }

            $layout = $this->layout;
            $this->layout = null;

            return $this->renderFile($layout, $data);
        }

        return $childOutput;
    }

    protected function renderFile(string $template, array $data): string
    {
        $path = static::path($template);

        if (! is_file($path)) {
            throw new RuntimeException("View [$template] not found at $path");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        include $path;

        return ob_get_clean();
    }

    protected static function path(string $template): string
    {
        return static::$basePath . '/' . str_replace('.', '/', $template) . '.php';
    }

    // --- Template API (called as $this->… from within a view) --------------

    protected function extends(string $layout): void
    {
        $this->layout = $layout;
    }

    protected function section(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    protected function endSection(): void
    {
        $name = array_pop($this->sectionStack);
        $this->sections[$name] = ob_get_clean();
    }

    protected function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    protected function insert(string $template, array $data = []): void
    {
        echo (new self())->renderFile($template, array_merge($this->data, $data));
    }

    protected function hasSection(string $name): bool
    {
        return isset($this->sections[$name]);
    }
}
