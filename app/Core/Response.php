<?php

namespace App\Core;

final class Response
{
    protected string $body;
    protected int $status;
    protected array $headers;

    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function make(string $body = '', int $status = 200, array $headers = []): self
    {
        return new self($body, $status, $headers);
    }

    public static function view(string $template, array $data = [], int $status = 200): self
    {
        return new self(View::render($template, $data), $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP),
            $status,
            ['Content-Type' => 'application/json']
        );
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public static function download(string $content, string $filename, string $contentType = 'application/octet-stream'): self
    {
        return new self($content, 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'attachment; filename="' . str_replace('"', '', $filename) . '"',
            'Content-Length'      => (string) strlen($content),
        ]);
    }

    public static function file(string $content, string $filename, string $contentType): self
    {
        return new self($content, 200, [
            'Content-Type'        => $contentType,
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $filename) . '"',
        ]);
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;

        return $this;
    }

    public function status(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function send(): void
    {
        if (! headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $key => $value) {
                header("$key: $value");
            }

            // Baseline security headers.
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }

        echo $this->body;
    }
}
