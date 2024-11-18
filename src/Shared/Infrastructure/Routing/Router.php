<?php

namespace App\Shared\Infrastructure\Routing;

use App\Shared\Infrastructure\Routing\Exception\RouteNotFoundException;
use App\Shared\Infrastructure\Container\Container;

class Router implements RouterInterface
{
    private array $routes = [];
    private array $middlewares = [];
    private array $groupAttributes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function get(string $path, callable $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function group(array $attributes, callable $callback): void
    {
        $previousGroupAttributes = $this->groupAttributes;
        
        $this->groupAttributes = $this->mergeGroupAttributes(
            $previousGroupAttributes,
            $attributes
        );

        $callback($this);

        $this->groupAttributes = $previousGroupAttributes;
    }

    public function middleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $route = $this->findRoute($method, $uri);
        
        if (!$route) {
            throw new RouteNotFoundException(
                "No route found for {$method} {$uri}"
            );
        }

        return $this->runRoute($route, ...array_values($route['params']));
    }

    private function addRoute(string $method, string $path, callable $handler): void
    {
        $path = $this->prependGroupPrefix($path);
        
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middlewares' => array_merge(
                $this->getGroupMiddlewares(),
                $this->middlewares
            ),
            'pattern' => $this->buildPattern($path)
        ];

        $this->middlewares = [];
    }

    private function findRoute(string $method, string $uri): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                return array_merge($route, [
                    'params' => $this->extractParams($matches)
                ]);
            }
        }

        return null;
    }

    private function runRoute(array $route, ...$params): mixed
    {
        $handler = $route['handler'];
        
        foreach (array_reverse($route['middlewares']) as $middleware) {
            $handler = function (...$params) use ($handler, $middleware) {
                return $this->container->get($middleware)->handle(
                    $handler,
                    ...$params
                );
            };
        }

        return $handler(...$params);
    }

    private function buildPattern(string $path): string
    {
        return '#^' . preg_replace(
            ['/\{([a-zA-Z]+)\}/', '/\/$/'],
            ['([^/]+)', '/?'],
            $path
        ) . '$#';
    }

    private function extractParams(array $matches): array
    {
        array_shift($matches);
        return $matches;
    }

    private function prependGroupPrefix(string $path): string
    {
        if (isset($this->groupAttributes['prefix'])) {
            return '/' . trim($this->groupAttributes['prefix'], '/') . '/' . trim($path, '/');
        }

        return $path;
    }

    private function getGroupMiddlewares(): array
    {
        return $this->groupAttributes['middlewares'] ?? [];
    }

    private function mergeGroupAttributes(array $previous, array $new): array
    {
        $middlewares = array_merge(
            $previous['middlewares'] ?? [],
            $new['middlewares'] ?? []
        );

        $prefix = isset($new['prefix'])
            ? trim($previous['prefix'] ?? '', '/') . '/' . trim($new['prefix'], '/')
            : ($previous['prefix'] ?? '');

        return array_merge($previous, $new, [
            'middlewares' => $middlewares,
            'prefix' => $prefix
        ]);
    }
} 