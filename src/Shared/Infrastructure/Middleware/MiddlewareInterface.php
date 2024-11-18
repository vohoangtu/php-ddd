<?php
namespace App\Shared\Infrastructure\Middleware;

interface MiddlewareInterface
{
    public function handle(callable $next, ...$params): mixed;
} 