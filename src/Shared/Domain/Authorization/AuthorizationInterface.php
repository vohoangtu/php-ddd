<?php

namespace App\Shared\Domain\Authorization;

interface AuthorizationInterface
{
    public function can(string $permission, $resource = null): bool;
    public function cannot(string $permission, $resource = null): bool;
    public function hasRole(string $role): bool;
    public function hasAnyRole(array $roles): bool;
} 