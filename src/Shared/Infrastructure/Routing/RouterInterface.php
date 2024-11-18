<?php
namespace App\Shared\Infrastructure\Routing;

interface RouterInterface
{
    public function get(string $path, callable $handler): void;
    public function post(string $path, callable $handler): void;
    public function put(string $path, callable $handler): void;
    public function delete(string $path, callable $handler): void;
    public function group(array $attributes, callable $callback): void;
    public function middleware(string $middleware): self;
    public function dispatch(string $method, string $uri): mixed;
} 