<?php
namespace App\Shared\Infrastructure\Container;

interface ContainerInterface
{
    public function get(string $id);
    public function has(string $id): bool;
    public function set(string $id, callable $factory): void;
} 