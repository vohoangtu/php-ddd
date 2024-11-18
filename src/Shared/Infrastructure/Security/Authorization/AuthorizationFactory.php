<?php

namespace App\Shared\Infrastructure\Security\Authorization;

use App\Shared\Infrastructure\Security\Authentication\AuthenticationInterface;

class AuthorizationFactory
{
    public static function create(
        string $type,
        AuthenticationInterface $auth,
        array $config = []
    ): AuthorizationInterface {
        return match ($type) {
            'rbac' => new RbacAuthorization($auth, $config['role_hierarchy'] ?? []),
            'abac' => new AbacAuthorization($auth),
            'hybrid' => new HybridAuthorization($auth, $config),
            default => throw new \InvalidArgumentException("Unknown authorization type: $type")
        };
    }
} 