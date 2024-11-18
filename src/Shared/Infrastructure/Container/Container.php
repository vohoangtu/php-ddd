<?php

namespace App\Shared\Infrastructure\Container;

use App\Shared\Infrastructure\Container\Exception\ContainerException;

class Container implements ContainerInterface
{
    private array $factories = [];
    private array $instances = [];
    private array $resolving = [];
    private array $providers = [];

    public function get(string $id)
    {
        if ($this->has($id)) {
            return $this->resolve($id);
        }

        throw ContainerException::serviceNotFound($id);
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->instances[$id]);
    }

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        unset($this->instances[$id]);
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = function () use ($factory) {
            static $instance;
            if ($instance === null) {
                $instance = $factory($this);
            }
            return $instance;
        };
    }

    public function instance(string $id, $instance): void
    {
        $this->instances[$id] = $instance;
        unset($this->factories[$id]);
    }

    public function register(ServiceProviderInterface $provider): void
    {
        $providerId = get_class($provider);
        
        if (isset($this->providers[$providerId])) {
            return;
        }

        $provider->register($this);
        
        foreach ($provider->provides() as $id) {
            $this->providers[$providerId][] = $id;
        }
    }

    private function resolve(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->resolving[$id])) {
            throw ContainerException::circularDependency($id);
        }

        $this->resolving[$id] = true;

        try {
            if (!isset($this->factories[$id])) {
                throw ContainerException::serviceNotFound($id);
            }

            $factory = $this->factories[$id];
            $instance = $factory($this);
            
            unset($this->resolving[$id]);
            
            return $instance;
        } catch (\Exception $e) {
            unset($this->resolving[$id]);
            throw $e;
        }
    }

    public function clear(): void
    {
        $this->factories = [];
        $this->instances = [];
        $this->resolving = [];
        $this->providers = [];
    }
} 