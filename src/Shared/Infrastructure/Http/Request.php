<?php

namespace App\Shared\Infrastructure\Http;

class Request
{
    private array $query;
    private array $request;
    private array $attributes;
    private array $cookies;
    private array $files;
    private array $server;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = []
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->attributes = $attributes;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
    }

    public static function createFromGlobals(): self
    {
        return new static(
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $_SERVER
        );
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'];
    }

    public function getUri(): string
    {
        return $this->server['REQUEST_URI'];
    }

    public function get(string $key, $default = null)
    {
        return $this->query[$key] ?? $this->request[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->query[$key]) || isset($this->request[$key]);
    }

    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function getHeader(string $key): ?string
    {
        $header = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$header] ?? null;
    }

    public function isXhr(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isSecure(): bool
    {
        return isset($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off';
    }

    public function getClientIp(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }
} 