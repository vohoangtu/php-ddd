<?php
namespace App\Shared\Infrastructure\Container\Exception;

class ContainerException extends \Exception
{
    public static function serviceNotFound(string $id): self
    {
        return new self("Service '$id' not found in container");
    }

    public static function circularDependency(string $id): self
    {
        return new self("Circular dependency detected while resolving '$id'");
    }

    public static function invalidService(string $id): self
    {
        return new self("Service '$id' must be defined with a callable factory");
    }
} 