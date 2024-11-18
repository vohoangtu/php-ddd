<?php

namespace App\Shared\Infrastructure\Middleware;

class MiddlewareStack
{
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function process(callable $handler, ...$params): mixed
    {
        $next = $handler;
        
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = fn(...$params) => $middleware->handle($next, ...$params);
        }

        return $next(...$params);
    }
} 